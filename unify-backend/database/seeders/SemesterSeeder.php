<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Semester;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        // Past semester (for archive dropdown demo)
        Semester::updateOrCreate(
            ['id' => '1402-2'],
            [
                'name' => 'نیم‌سال دوم ۱۴۰۲-۱۴۰۳',
                'is_current' => false,
                'global_state' => 'exam',
                'start_date_g' => '2024-01-20 08:00:00',
                'end_date_g' => '2024-06-20 18:00:00',
                'shamsi_original_start' => '1402/11/01',
                'shamsi_original_end' => '1403/04/01',
            ]
        );

        // Current semester (enrolling)
        Semester::updateOrCreate(
            ['id' => '1403-1'],
            [
                'name' => 'نیم‌سال اول ۱۴۰۳-۱۴۰۴',
                'is_current' => true,
                'global_state' => 'enrolling',
                'start_date_g' => '2024-09-20 08:00:00',
                'end_date_g' => '2025-01-20 18:00:00',
                'shamsi_original_start' => '1403/07/01',
                'shamsi_original_end' => '1403/11/01',
            ]
        );

        $this->command->info('Semesters seeded: 1403-1 (current), 1402-2 (past).');
    }
}
