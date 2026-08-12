<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            SemesterSeeder::class,
            OwnerSeeder::class,
            AdminSeeder::class,
            ProfessorSeeder::class,
            ExpertSeeder::class,
            HeadOfDeptSeeder::class,
            StudentSeeder::class,
            CourseSeeder::class,
            CourseSpecificationSeeder::class,
            CurriculumSeeder::class,
            SystemConfigSeeder::class,
            AcademicCalendarSeeder::class,
        ]);
    }
}
