<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Enrollment;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class EnrollmentsWipeGrace extends Command
{
    protected $signature = 'enrollments:wipe-grace';
    protected $description = 'Hard delete temporary enrollments after grace period + lazy check';

    public function handle()
    {
        $semester = \App\Models\Semester::where('is_current', true)->first();
        if (!$semester || !$semester->grace_period_ends_at || now()->lessThan($semester->grace_period_ends_at)) {
            return;
        }

        $tempEnrollments = Enrollment::where('status', 'temporary')
            ->where('semester_id', $semester->id)
            ->get();

        DB::transaction(function () use ($tempEnrollments, $semester) {
            foreach ($tempEnrollments as $enr) {
                $enr->delete(); // Hard delete

                // Notify student
                Notification::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'user_id' => $enr->student_id,
                    'type' => 'grace_ended',
                    'title' => 'دوره grace تمام شد',
                    'body' => 'انتخاب واحد موقت شما حذف شد',
                    'priority' => 'high',
                    'created_at' => now(),
                ]);
            }

            $semester->update(['grace_period_handled' => true]);
        });

        $this->info(count($tempEnrollments) . ' temporary enrollments wiped.');
    }
}