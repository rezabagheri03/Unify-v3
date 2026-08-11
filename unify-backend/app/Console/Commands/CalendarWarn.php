<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicCalendar;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Semester;
use Illuminate\Support\Str;

class CalendarWarn extends Command
{
    protected $signature = 'calendar:warn';
    protected $description = 'Send 7-day and 24h warnings for academic calendar events';

    /**
     * TODO-018 fix. Two defects removed:
     *  1) Recipients were resolved via academic_calendars.semester_id — a column
     *     that never existed, so zero notifications were ever sent. Targets are
     *     now finalized students of the CURRENT semester, department-scoped for
     *     departmental events.
     *  2) Every event inside the next 7 days was re-notified on EVERY daily run.
     *     Each (user, event, window) is now sent exactly once (dedupe via the
     *     JSON data column).
     */
    public function handle()
    {
        $semester = Semester::where('is_current', true)->first();
        if (! $semester) {
            $this->warn('No current semester defined. Skipping calendar warnings.');
            return 0;
        }

        $windows = [
            '7d'  => [now()->addDays(6)->addHours(12), now()->addDays(7)->addHours(12)],
            '24h' => [now(), now()->addHours(24)],
        ];

        $sent = 0;
        foreach ($windows as $tag => [$from, $to]) {
            $events = AcademicCalendar::whereBetween('start_date_g', [$from, $to])
                ->where('is_active', true)
                ->get();

            foreach ($events as $event) {
                foreach ($this->targetStudents($event, $semester->id) as $studentId) {
                    $alreadySent = Notification::where('user_id', $studentId)
                        ->where('type', 'calendar_warning')
                        ->where('data->event_id', $event->id)
                        ->where('data->window', $tag)
                        ->exists();
                    if ($alreadySent) {
                        continue;
                    }

                    Notification::create([
                        'id' => (string) Str::uuid(),
                        'user_id' => $studentId,
                        'type' => 'calendar_warning',
                        'title' => 'یادآوری تقویم',
                        'body' => $event->title . ' - ' . $event->start_date_g,
                        'data' => ['event_id' => $event->id, 'window' => $tag],
                        'priority' => 'high',
                        'created_at' => now(),
                    ]);
                    $sent++;
                }
            }
        }

        $this->info($sent . ' calendar warnings sent.');
        return 0;
    }

    /** Finalized students of the current semester, department-scoped when needed. */
    private function targetStudents(AcademicCalendar $event, string $semesterId)
    {
        $query = Enrollment::where('semester_id', $semesterId)
            ->where('status', 'finalized');

        if (! $event->is_university_wide && $event->department_id) {
            $query->whereHas('specification.course', fn ($q) => $q->where('department_id', $event->department_id));
        }

        return $query->distinct()->pluck('student_id');
    }
}
