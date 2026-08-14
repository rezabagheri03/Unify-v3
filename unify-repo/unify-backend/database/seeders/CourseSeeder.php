<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $csv = database_path('seed-data/seed_courses_40.csv');
        if (! file_exists($csv)) {
            $this->command->error('seed_courses_40.csv not found.');
            return;
        }

        $rows = array_map('str_getcsv', file($csv));
        $header = array_shift($rows); // Course Code, Course Name, Credits, Department ID

        $created = 0;
        foreach ($rows as $row) {
            [$code, $name, $credits, $dept] = array_pad($row, 4, null);
            if (! $code || ! $name) {
                continue;
            }
            Course::updateOrCreate(
                ['code' => trim($code)],
                [
                    'id' => trim($code), // id = code for stable references
                    'name' => trim($name),
                    'credits' => (int) $credits ?: 3,
                    'department_id' => trim($dept) ?: 'CS',
                    'is_active' => true,
                ]
            );
            $created++;
        }

        $this->seedPrerequisites();
        $this->command->info("Courses seeded: {$created} (CSV) + prerequisites.");
    }

    /**
     * Seed course_prerequisites + course_corequisites from seed_curriculum_1401.csv
     * (Prerequisites column may hold comma-separated course codes).
     */
    private function seedPrerequisites(): void
    {
        $csv = database_path('seed-data/seed_curriculum_1401.csv');
        if (! file_exists($csv)) {
            return;
        }

        $rows = array_map('str_getcsv', file($csv));
        array_shift($rows); // header

        foreach ($rows as $row) {
            [$entryYear, $courseCode, $isRequired, $prereqs, $semester] = array_pad($row, 5, null);
            $courseCode = trim((string) $courseCode);
            if (! $courseCode || ! Course::where('code', $courseCode)->exists()) {
                continue;
            }

            foreach (explode(',', (string) $prereqs) as $prereq) {
                $prereq = trim($prereq);
                if ($prereq === '' || ! Course::where('code', $prereq)->exists()) {
                    continue;
                }
                DB::table('course_prerequisites')->updateOrInsert(
                    ['course_id' => $courseCode, 'prerequisite_id' => $prereq],
                    []
                );
            }
        }
    }
}
