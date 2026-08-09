<?php

namespace App\Services;

use App\Models\CourseSpecification;
use Illuminate\Support\Collection;

class GoldenSchedulerService
{
    public function generate(array $availableSpecIds, array $preferences = []): array
    {
        $specs = CourseSpecification::whereIn('id', $availableSpecIds)
            ->with('course')
            ->get()
            ->toArray();

        $combinations = $this->findValidCombinations($specs, $preferences);
        return $this->scoreAndRank($combinations, $preferences);
    }

    private function findValidCombinations(array $specs, array $preferences, int $maxResults = 50): array
    {
        $results = [];
        $usedDays = [];
        $usedTimeSlots = [];

        // Sort by MRV (Minimum Remaining Values) heuristic
        usort($specs, function ($a, $b) {
            return ($a['course']['credits'] ?? 0) <=> ($b['course']['credits'] ?? 0);
        });

        $this->backtrack($specs, [], $results, $usedDays, $usedTimeSlots, $preferences, 0, $maxResults);
        return $results;
    }

    private function backtrack(array $specs, array $current, array &$results, array &$usedDays, array &$usedTimeSlots, array $preferences, int $startIndex, int $maxResults)
    {
        if (count($results) >= $maxResults) return;

        if (count($current) >= 4 && count($current) <= 8) {
            $results[] = $current;
        }

        for ($i = $startIndex; $i < count($specs); $i++) {
            $spec = $specs[$i];

            if ($this->canAddSpec($spec, $usedDays, $usedTimeSlots)) {
                $current[] = $spec;
                $usedDays[$spec['day_of_week']] = true;
                $usedTimeSlots[] = [$spec['time_start'], $spec['time_end'], $spec['is_next_day']];

                $this->backtrack($specs, $current, $results, $usedDays, $usedTimeSlots, $preferences, $i + 1, $maxResults);

                array_pop($current);
                unset($usedDays[$spec['day_of_week']]);
                array_pop($usedTimeSlots);
            }
        }
    }

    private function canAddSpec(array $spec, array $usedDays, array $usedTimeSlots): bool
    {
        if (isset($usedDays[$spec['day_of_week']])) {
            return false;
        }

        foreach ($usedTimeSlots as [$start, $end, $isNextDay]) {
            if ($this->timesOverlap($spec['time_start'], $spec['time_end'], $start, $end, $spec['is_next_day'], $isNextDay)) {
                return false;
            }
        }

        return true;
    }

    private function timesOverlap($start1, $end1, $start2, $end2, $isNext1, $isNext2): bool
    {
        // Simplified overlap check (can be improved)
        return max($start1, $start2) < min($end1, $end2);
    }

    private function scoreAndRank(array $combinations, array $preferences): array
    {
        $scored = collect($combinations)->map(function ($combo) use ($preferences) {
            $credits = collect($combo)->sum(fn($s) => $s['course']['credits'] ?? 0);
            $freeDays = 6 - count(array_unique(array_column($combo, 'day_of_week')));
            $score = ($freeDays * 20) - (count($combo) * 3) + 15;

            return [
                'specs' => $combo,
                'credits' => $credits,
                'score' => $score,
                'explanation' => "$freeDays روز آزاد",
            ];
        });

        return $scored->sortByDesc('score')->take(15)->values()->toArray();
    }
}