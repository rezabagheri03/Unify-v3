<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::updateOrCreate(
            ['id' => 'CS'],
            ['name_fa' => 'مهندسی کامپیوتر', 'name_en' => 'Computer Engineering']
        );
    }
}
