<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            OwnerSeeder::class,
            AdminSeeder::class,
            ProfessorSeeder::class,
            ExpertSeeder::class,
            HeadOfDeptSeeder::class,
            StudentSeeder::class,
        ]);
    }
}