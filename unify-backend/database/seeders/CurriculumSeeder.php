<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Course;
use App\Models\StudentPassedCourse;
use App\Models\User;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('seed-data/seed_curriculum_1401.csv');
        if (! file_exists($csv)) {
            $this->command->error('seed_curriculum_1401.csv not found.');
            return;
        }

        $rows = array_map('str_getcsv', file($csv));
        array_shift($rows); // header

        // Courses suggested for semester 1 of entry year 1401 -> "passed" by year-2 students.
        $yearOneCourses = [];
        foreach ($rows as $row) {
            [$entryYear, $courseCode, $isRequired, $prereqs, $semester] = array_pad($row, 5, null);
            if (trim((string) $semester) === '1' && Course::where('code', trim($courseCode))->exists()) {
                $yearOneCourses[] = trim($courseCode);
            }
        }

        $students = User::where('role', 'student')->where('id', 'like', '4001%')->get();
        $created = 0;

        foreach ($students as $student) {
            $passedCount = max(1, min(count($yearOneCourses), 2 + (int) substr($student->id, -2) % (count($yearOneCourses))));
            $chosen = array_slice($yearOneCourses, 0, $passedCount);

            foreach ($chosen as $courseCode) {
                StudentPassedCourse::updateOrCreate(
                    ['student_id' => $student->id, 'course_id' => $courseCode, 'entry_year' => 1401],
                    [
                        'id' => (string) Str::uuid(),
                        'passed' => true,
                        'grade' => round(12 + ((int) substr($student->id, -2) % 8), 1),
                        'created_at' => now(),
                    ]
                );
                $created++;
            }
        }

        $this->command->info("Curriculum seeded: {$created} StudentPassedCourse rows (entry year 1401).");
    }
}
