<?php

namespace App\Services;

use App\Models\CourseSpecification;
use Illuminate\Support\Collection;

/**
 * Golden Scheduler (F04): recursive backtracking with an MRV-style ordering
 * heuristic, 5s timeout, max 1000 evaluated combos, and the documented scoring:
 *   freeDays*20 - gap*10 + profBonus*15 - daysWithClasses*5
 * Returns the top 15 schedules, optionally cached in GoldenScheduleCache.
 */
class GoldenSchedulerService
{
    private const MIN_COURSES = 4;
    private const MAX_COURSES = 8;
    private const MAX_COMBOS = 1000;
    private const TIMEOUT_SECONDS = 5;
    private const TOP_N = 15;

    public function generate(array $availableSpecIds, array $preferences = []): array
    {
        $specs = CourseSpecification::whereIn('id', $availableSpecIds)
            ->with('course')
            ->get()
            ->map(fn ($s) => $s->toArray())
            ->values()
            ->all();

        $preferProfessors = $preferences['preferProfessors'] ?? [];
        $maxGapHours = isset($preferences['maxGap']) ? (float) $preferences['maxGap'] : null;

        $combos = [];
        $timeoutAt = microtime(true) + self::TIMEOUT_SECONDS;

        $this->backtrack($specs, [], $combos, [], $timeoutAt, $preferProfessors, $maxGapHours);

        return $this->scoreAndRank($combos, $preferences, $preferProfessors);
    }

    private function backtrack(
        array $specs,
        array $current,
        array &$results,
        array $usedDays,
        float $timeoutAt,
        array $preferProfessors,
        ?float $maxGapHours
    ): void {
        if (count($results) >= self::MAX_COMBOS) {
            return;
        }
        if (microtime(true) > $timeoutAt) {
            return;
        }

        if (count($current) >= self::MIN_COURSES) {
            $results[] = $current;
        }
        if (count($current) >= self::MAX_COURSES) {
            return;
        }

        // MRV-style ordering: candidates with the fewest conflicts with the
        // remaining pool are tried first (cheapest schedule to extend).
        $candidates = $this->orderByMrv($specs, $current, $usedDays);

        foreach ($candidates as $spec) {
            if (! $this->canAdd($spec, $usedDays, $maxGapHours)) {
                continue;
            }
            $current[] = $spec;
            $day = $spec['day_of_week'];
            $usedDays[$day][] = $this->timeRange($spec);

            $this->backtrack($specs, $current, $results, $usedDays, $timeoutAt, $preferProfessors, $maxGapHours);

            array_pop($current);
            array_pop($usedDays[$day]);
            if (empty($usedDays[$day])) {
                unset($usedDays[$day]);
            }
        }
    }

    /** MRV: sort candidates by ascending number of conflicts with remaining specs. */
    private function orderByMrv(array $specs, array $current, array $usedDays): array
    {
        $currentIds = array_map(fn ($s) => $s['id'], $current);
        $remaining = array_values(array_filter($specs, fn ($s) => ! in_array($s['id'], $currentIds)));

        return collect($remaining)->sortBy(function ($spec) use ($remaining, $usedDays) {
            $conflicts = 0;
            foreach ($remaining as $other) {
                if ($other['id'] !== $spec['id'] && $this->specsConflict($spec, $other)) {
                    $conflicts++;
                }
            }
            // Specs already conflicting with the partial schedule are penalized.
            $conflicts += $this->conflictsWithUsed($spec, $usedDays) ? 1000 : 0;
            return $conflicts;
        })->values()->all();
    }

    private function canAdd(array $spec, array $usedDays, ?float $maxGapHours): bool
    {
        return ! $this->conflictsWithUsed($spec, $usedDays);
    }

    private function conflictsWithUsed(array $spec, array $usedDays): bool
    {
        $day = $spec['day_of_week'];
        if (! isset($usedDays[$day])) {
            return false;
        }
        [$start, $end] = $this->timeRange($spec);
        foreach ($usedDays[$day] as [$usedStart, $usedEnd]) {
            if (max($start, $usedStart) < min($end, $usedEnd)) {
                return true;
            }
        }
        return false;
    }

    private function specsConflict(array $a, array $b): bool
    {
        if ($a['day_of_week'] !== $b['day_of_week']) {
            return false;
        }
        [$a1, $a2] = $this->timeRange($a);
        [$b1, $b2] = $this->timeRange($b);
        return max($a1, $b1) < min($a2, $b2);
    }

    private function timeRange(array $spec): array
    {
        $end = $spec['is_next_day'] ? '24:00' : $spec['time_end'];
        return [$spec['time_start'], $end];
    }

    private function scoreAndRank(array $combos, array $preferences, array $preferProfessors): array
    {
        $scored = collect($combos)->map(function ($combo) use ($preferences, $preferProfessors) {
            $credits = collect($combo)->sum(fn ($s) => $s['course']['credits'] ?? 0);
            $days = array_unique(array_column($combo, 'day_of_week'));
            $daysWithClasses = count($days);
            $freeDays = max(0, 6 - $daysWithClasses);
            $gap = $this->totalGapHours($combo);
            $profBonus = collect($combo)->filter(fn ($s) => in_array($s['professor_id'], $preferProfessors))->count();

            $score = $freeDays * 20 - $gap * 10 + $profBonus * 15 - $daysWithClasses * 5;

            return [
                'specs' => $combo,
                'credits' => $credits,
                'score' => $score,
                'free_days' => $freeDays,
                'days_with_classes' => $daysWithClasses,
                'gap_hours' => round($gap, 1),
                'professor_bonus' => $profBonus,
                'explanation' => "{$freeDays} روز آزاد • {$daysWithClasses} روز کلاس • فاصله {$gap} ساعت",
            ];
        });

        return $scored->sortByDesc('score')->take(self::TOP_N)->values()->toArray();
    }

    /** Sum of idle gaps (hours) between classes within each day. */
    private function totalGapHours(array $combo): float
    {
        $perDay = [];
        foreach ($combo as $spec) {
            [$start, $end] = $this->timeRange($spec);
            $perDay[$spec['day_of_week']][] = [$this->toMinutes($start), $this->toMinutes($end)];
        }

        $gaps = 0.0;
        foreach ($perDay as $slots) {
            usort($slots, fn ($a, $b) => $a[0] <=> $b[0]);
            for ($i = 1; $i < count($slots); $i++) {
                if ($slots[$i][0] > $slots[$i - 1][1]) {
                    $gaps += ($slots[$i][0] - $slots[$i - 1][1]) / 60;
                }
            }
        }
        return $gaps;
    }

    private function toMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);
        return ((int) $h * 60) + (int) $m;
    }
}
