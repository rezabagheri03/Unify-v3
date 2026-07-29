<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicCalendar;
use App\Models\Notification;
use App\Models\Enrollment;

class CalendarWarn extends Command
{
    protected $signature = 'calendar:warn';
    protected $description = 'Send 7-day and 24h warnings for academic calendar events';

    public function handle()
    {
        $events = AcademicCalendar::whereBetween('start_date_g', [now(), now()->addDays(7)])
            ->where('is_active', true)
            ->get();

        foreach ($events as $event) {
            $enrolledStudents = Enrollment::where('semester_id', $event->semester_id ?? null)
                ->where('status', 'finalized')
                ->pluck('student_id');

            foreach ($enrolledStudents as $studentId) {
                Notification::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $studentId,
                    'type' => 'calendar_warning',
                    'title' => 'یادآوری تقویم',
                    'body' => $event->title . ' - ' . $event->start_date_g,
                    'priority' => 'high',
                    'created_at' => now(),
                ]);
            }
        }

        $this->info(count($events) . ' calendar warnings sent.');
    }
}