<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\AcademicCalendar;

class AcademicCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            ['title' => 'شروع ثبت‌نام', 'description' => 'آغاز ثبت‌نام اینترنتی واحدها', 'start' => '2024-09-20 08:00:00', 'end' => '2024-09-28 18:00:00', 'type' => 'registration_open', 'color' => '#4CAF50'],
            ['title' => 'پایان ثبت‌نام', 'description' => 'پایان مهلت ثبت‌نام و حذف و اضافه', 'start' => '2024-09-28 18:00:00', 'end' => '2024-09-28 18:00:00', 'type' => 'registration_close', 'color' => '#F44336'],
            ['title' => 'شروع نیم‌سال', 'description' => 'آغاز کلاس‌های نیم‌سال اول', 'start' => '2024-09-29 08:00:00', 'end' => '2024-09-29 08:00:00', 'type' => 'semester_start', 'color' => '#2196F3'],
            ['title' => 'آغاز امتحانات', 'description' => 'شروع امتحانات پایان ترم', 'start' => '2025-01-05 08:00:00', 'end' => '2025-01-20 18:00:00', 'type' => 'exam_period_start', 'color' => '#FF9800'],
            ['title' => 'تعطیل رسمی', 'description' => 'تعطیلات رسمی', 'start' => '2024-10-02 00:00:00', 'end' => '2024-10-04 23:59:00', 'type' => 'holiday', 'color' => '#9E9E9E'],
        ];

        foreach ($events as $e) {
            AcademicCalendar::create([
                'id' => (string) Str::uuid(),
                'title' => $e['title'],
                'description' => $e['description'],
                'start_date_g' => $e['start'],
                'end_date_g' => $e['end'],
                'event_type' => $e['type'],
                'is_university_wide' => true,
                'color_code' => $e['color'],
            ]);
        }

        $this->command->info('Academic calendar seeded: ' . count($events) . ' events.');
    }
}
