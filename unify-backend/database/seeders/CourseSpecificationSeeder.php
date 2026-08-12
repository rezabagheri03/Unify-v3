<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Course;
use App\Models\CourseSpecification;
use App\Models\User;
use App\Services\ShamsiService;

class CourseSpecificationSeeder extends Seeder
{
    /** Persian day names -> ENUM values (ZWNJ-insensitive). */
    private const DAY_MAP = [
        'شنبه' => 'sat', 'یکشنبه' => 'sun', 'دوشنبه' => 'mon', 'سهشنبه' => 'tue',
        'چهارشنبه' => 'wed', 'پنجشنبه' => 'thu', 'جمعه' => 'fri',
    ];

    private const TIME_SLOTS = [
        ['08:00', '10:00'], ['10:00', '12:00'], ['13:00', '15:00'],
        ['15:00', '17:00'], ['17:00', '19:00'],
    ];

    private const DAYS = ['sat', 'sun', 'mon', 'tue', 'wed'];

    public function run(): void
    {
        $semesterId = '1403-1';

        // 1) Exact rows from the provided CSV (10 sample specs)
        $csv = database_path('seed-data/seed_specifications_100.csv');
        $seeded = 0;
        if (file_exists($csv)) {
            $rows = array_map('str_getcsv', file($csv));
            array_shift($rows); // header
            foreach ($rows as $row) {
                [$courseCode, $profId, $dayFa, $start, $end, $location, $telegram, $finalShamsi, $midShamsi, $semester] = array_pad($row, 10, null);
                if (! $courseCode || ! Course::where('code', trim($courseCode))->exists()) {
                    continue;
                }
                $day = $this->normalizeDay($dayFa);
                if (! $day) {
                    continue;
                }

                CourseSpecification::firstOrCreate(
                    [
                        'course_id' => trim($courseCode),
                        'professor_id' => trim($profId) ?: 'P1001',
                        'day_of_week' => $day,
                        'time_start' => $start,
                        'time_end' => $end,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'semester_id' => trim($semester) ?: $semesterId,
                        'location' => $location ?: 'کلاس ۱۰۱',
                        'telegram_link' => $telegram ?: null,
                        'exam_date_final_g' => $finalShamsi ? ShamsiService::toGregorian(trim($finalShamsi)) : null,
                        'shamsi_original_final' => $finalShamsi ? trim($finalShamsi) : null,
                        'exam_date_midterm_g' => $midShamsi ? ShamsiService::toGregorian(trim($midShamsi)) : null,
                        'shamsi_original_midterm' => $midShamsi ? trim($midShamsi) : null,
                        'is_active' => true,
                        'is_next_day' => false,
                    ]
                );
                $seeded++;
            }
        }

        // 2) Generate enough specs to reach ~100 for a realistic enrollment pool.
        $professors = User::where('role', 'professor')->pluck('id')->values()->all();
        if (empty($professors)) {
            $professors = ['P1001', 'P1002', 'P1003'];
        }

        $courses = Course::orderBy('code')->get();
        $existing = CourseSpecification::where('semester_id', $semesterId)->pluck('course_id');
        $targetPerCourse = 3;

        $generated = 0;
        foreach ($courses as $i => $course) {
            $have = $existing->filter(fn($c) => $c === $course->code)->count();
            $need = max(0, $targetPerCourse - $have);
            for ($j = 0; $j < $need; $j++) {
                $day = self::DAYS[($i + $j) % count(self::DAYS)];
                [$start, $end] = self::TIME_SLOTS[($i + $j) % count(self::TIME_SLOTS)];
                $prof = $professors[($i + $j) % count($professors)];
                $examShamsi = '1403/04/' . str_pad((string) (10 + (($i + $j) % 18)), 2, '0', STR_PAD_LEFT);

                CourseSpecification::create([
                    'id' => (string) Str::uuid(),
                    'course_id' => $course->code,
                    'professor_id' => $prof,
                    'day_of_week' => $day,
                    'time_start' => $start,
                    'time_end' => $end,
                    'location' => 'کلاس ' . (100 + (($i + $j) % 20)),
                    'telegram_link' => 'https://t.me/unify_cs_' . strtolower($course->code),
                    'exam_date_final_g' => ShamsiService::toGregorian($examShamsi),
                    'shamsi_original_final' => $examShamsi,
                    'semester_id' => $semesterId,
                    'is_active' => true,
                    'is_next_day' => false,
                ]);
                $generated++;
            }
        }

        $total = CourseSpecification::where('semester_id', $semesterId)->count();
        $this->command->info("Course specifications seeded: {$seeded} from CSV + {$generated} generated = {$total} total for {$semesterId}.");
    }

    private function normalizeDay(?string $fa): ?string
    {
        if (! $fa) {
            return null;
        }
        // Strip ZWNJ (U+200C) so 'سه‌شنبه' and 'سهشنبه' both match.
        $clean = str_replace("\u{200C}", '', trim($fa));
        return self::DAY_MAP[$clean] ?? null;
    }
}
