<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Semester;
use App\Models\Enrollment;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

// FIX C3: Grace period wipe - now every 5 min + lazy check fallback in EnrollmentController@final
class EnrollmentsWipeGrace extends Command
{
    protected $signature = 'enrollments:wipe-grace';
    protected $description = 'Hard delete temporary enrollments after 24h grace period ended';

    public function handle()
    {
        $semesters = Semester::where('global_state', 'active')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now())
            ->where('grace_period_handled', false)
            ->get();

        foreach ($semesters as $semester) {
            DB::transaction(function() use ($semester) {
                // Find students whose temp will be wiped for notification
                $affectedStudentIds = Enrollment::where('semester_id', $semester->id)
                    ->where('status', 'temporary')
                    ->pluck('student_id')->unique();

                $count = Enrollment::where('semester_id', $semester->id)
                    ->where('status', 'temporary')
                    ->delete();

                $semester->grace_period_handled = true;
                $semester->save();

                // Notify affected
                foreach ($affectedStudentIds as $studentId) {
                    Notification::create([
                        'id' => \Str::uuid(),
                        'user_id' => $studentId,
                        'type' => 'grace_period_ended',
                        'title' => 'مهلت نهایی‌سازی تمام شد',
                        'body' => 'لیست موقت شما پس از پایان مهلت 24 ساعته حذف شد - برنامه شما خالی است - با آموزش تماس بگیرید',
                        'data' => json_encode(['semester_id' => $semester->id]),
                        'priority' => 'critical',
                        'read' => false,
                        'created_at' => now(),
                    ]);
                    // Pushe via service
                    // app(\App\Services\PusheService::class)->send([$studentId], 'مهلت تمام شد', 'لیست موقت حذف شد');
                }

                \Log::info("Grace period wiped $count temporary enrollments for semester {$semester->id}");
            });
        }
    }
}
