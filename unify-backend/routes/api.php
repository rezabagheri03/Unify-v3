<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SpecificationController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GoldenScheduleController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\StickyNoteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\NoticeBoardController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\AcademicCalendarController;
use App\Http\Controllers\Api\Curriculum\CurriculumChartController;
use App\Http\Controllers\Api\Admin\ResourceApprovalController;
use App\Http\Controllers\Api\Admin\SemesterController;
use App\Http\Controllers\Api\Owner\UserController as OwnerUserController;
use App\Http\Controllers\Api\Owner\AuditLogController;
use App\Http\Controllers\Api\Excel\ExportController;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Controllers\Api\BroadcastThrottleController;
use App\Http\Controllers\Api\OfflineSyncController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MonitoringController;

/*
|--------------------------------------------------------------------------
| Unify V9 API — versioned under /api/v1 per 23_API_VERSIONING.md
|--------------------------------------------------------------------------
*/

// Public (kept unprefixed for monitoring/intranet health checks)
Route::get('/health', [HealthController::class, 'check']);

Route::prefix('v1')->group(function () {

    // ---- Public ----
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:' . env('LOGIN_THROTTLE_MAX_ATTEMPTS', 5) . ',' . env('LOGIN_THROTTLE_DECAY_MINUTES', 15));
    Route::get('/branding', [BrandingController::class, 'publicConfig']);
    // Health probe (21_MONITORING.md + 12_SECURITY_CHECKLIST.md: /api/v1/health)
    Route::get('/health', [HealthController::class, 'check']);

    // ---- Authenticated ----
    // Post-audit F-06: a global per-user throttle bounds EVERY authenticated
    // endpoint (sync/devices/notifications/forms previously had none); the
    // stricter per-group limits below still dominate. Post-audit F-01: the
    // password-rotation gate runs on every authenticated route.
    Route::middleware(['auth:sanctum', 'password.changed', 'throttle:120,1'])->group(function () {

        // Auth (with per-request logging so failures are visible)
        Route::post('/onboarding', [AuthController::class, 'onboarding'])->middleware('reqlog');
        Route::post('/password/change', [AuthController::class, 'changePassword'])->middleware('reqlog');
        // SEC-03 fix: real logout that revokes the presenting token.
        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('reqlog');
        Route::get('/users/me', fn (Request $request) => $request->user())->middleware('reqlog');

        // Honor
        Route::get('/users/me/academic-status', [UserController::class, 'getAcademicStatus']);
        Route::post('/users/me/academic-status', [UserController::class, 'declareAcademicStatus']);

        // Scheduler
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/specifications', [SpecificationController::class, 'index']);
            Route::get('/enrollment/temp', [EnrollmentController::class, 'indexTemp']);
            Route::post('/enrollment/temp', [EnrollmentController::class, 'storeTemp']);
            Route::delete('/enrollment/temp/{id}', [EnrollmentController::class, 'removeTemp']);
            Route::post('/enrollment/final', [EnrollmentController::class, 'finalize']);
            Route::get('/enrollments', [EnrollmentController::class, 'myEnrollments']);
            Route::get('/golden-schedule', [GoldenScheduleController::class, 'generate']);
        });

        // Semesters
        Route::get('/semesters/current', [SemesterController::class, 'current']);
        Route::get('/semesters/past', [SemesterController::class, 'past']);

        // Resources
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/resources', [ResourceController::class, 'index']);
            Route::get('/resources/{id}', [ResourceController::class, 'show']);
            Route::post('/resources/upload', [ResourceController::class, 'upload']);
            Route::post('/resources/{id}/new-version', [ResourceController::class, 'newVersion']);
            Route::get('/resources/{id}/download', [ResourceController::class, 'download']);
            Route::post('/resources/{id}/rating', [RatingController::class, 'store']);
            Route::get('/resources/{id}/sticky-note', [StickyNoteController::class, 'show']);
            Route::post('/resources/{id}/sticky-note', [StickyNoteController::class, 'store']);
        });

        // Messages
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/messages', [MessageController::class, 'index']);
            Route::get('/messages/{id}', [MessageController::class, 'show']);
            Route::post('/messages/send', [MessageController::class, 'send']);
            Route::patch('/messages/{id}', [MessageController::class, 'edit']);
            Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
            Route::post('/messages/{id}/read', [MessageController::class, 'markRead']);
            Route::post('/broadcast/check', [BroadcastThrottleController::class, 'check']);
        });

        // Tickets
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/tickets', [TicketController::class, 'index']);
            Route::post('/tickets', [TicketController::class, 'store']);
            Route::get('/tickets/{id}', [TicketController::class, 'show']);
            Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);
            Route::patch('/tickets/{id}/status', [TicketController::class, 'updateStatus']);
            Route::patch('/tickets/{id}/assign', [TicketController::class, 'assign']);
        });

        // Monitoring (21_MONITORING.md)
        Route::get('/monitoring/health', [MonitoringController::class, 'health']);
        Route::get('/monitoring/storage', [MonitoringController::class, 'storage']);

        // Notifications (polling)
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/mute', [NotificationController::class, 'mute']);

        // Devices + offline sync
        Route::post('/devices', [DeviceController::class, 'register']);
        Route::delete('/devices', [DeviceController::class, 'destroy']);
        Route::post('/offline/sync', [OfflineSyncController::class, 'sync']);

        // Assignments
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/assignments', [AssignmentController::class, 'index']);
            Route::post('/assignments', [AssignmentController::class, 'store']);
            Route::patch('/assignments/{id}', [AssignmentController::class, 'update']);
            Route::post('/assignments/{id}/submit', [AssignmentController::class, 'submit']);
            Route::post('/assignments/{id}/grade', [AssignmentController::class, 'grade']);
        });

        // Curriculum
        Route::middleware('throttle:30,1')->group(function () {
            Route::get('/curriculum', [CurriculumChartController::class, 'index']);
            Route::post('/curriculum', [CurriculumChartController::class, 'store']);
            Route::post('/curriculum/{id}/submit', [CurriculumChartController::class, 'submitForApproval']);
            Route::post('/curriculum/{id}/approve', [CurriculumChartController::class, 'approve']);
            Route::post('/curriculum/{id}/reject', [CurriculumChartController::class, 'reject']);
        });

        // Forms / NoticeBoard / FAQ / Academic Calendar
        // SEC-02 fix (ROLES): publishing official content is a staff capability —
        // students used to be able to POST all of these. Forms are Admin
        // (university-level) + Expert (department-level); professors explicitly
        // CANNOT publish forms per docs/ROLES. Notice-board additionally allows
        // professors (their own class notices).
        Route::get('/forms', [FormController::class, 'index']);
        Route::post('/forms', [FormController::class, 'store'])->middleware('role:expert,admin');
        Route::get('/forms/{id}/download', [FormController::class, 'download']);
        Route::get('/notice-board', [NoticeBoardController::class, 'index']);
        Route::post('/notice-board', [NoticeBoardController::class, 'store'])->middleware('role:professor,expert,admin');
        Route::get('/faqs', [FaqController::class, 'index']);
        Route::post('/faqs', [FaqController::class, 'store'])->middleware('role:expert,admin');
        Route::get('/academic-calendar', [AcademicCalendarController::class, 'index']);
        Route::post('/academic-calendar', [AcademicCalendarController::class, 'store'])->middleware('role:expert,admin');

        // ---- Admin (expert + admin) ----
        Route::middleware('role:expert,admin')->group(function () {
            Route::get('/admin/resources/pending', [ResourceApprovalController::class, 'pending']);
            Route::post('/admin/resources/{id}/approve', [ResourceApprovalController::class, 'approve']);
            Route::post('/admin/resources/{id}/reject', [ResourceApprovalController::class, 'reject']);
            Route::post('/admin/semesters', [SemesterController::class, 'createNewSemester']);
            // BE audit fix: the enrolling -> active transition (grace start).
            Route::post('/admin/semesters/activate', [SemesterController::class, 'activateCurrent']);
            Route::post('/admin/branding/logo', [BrandingController::class, 'uploadLogo']);
            // Round-2 (V2-17c): setBrandName existed as an unrouted method.
            Route::post('/admin/branding/name', [BrandingController::class, 'setBrandName']);
            // BE audit fix: these import handlers existed without routes.
            Route::post('/admin/import/courses', [\App\Http\Controllers\Api\Excel\ImportController::class, 'importCourses']);
            Route::post('/admin/import/specifications', [\App\Http\Controllers\Api\Excel\ImportController::class, 'importSpecifications']);
        });

        // ---- Owner ----
        Route::middleware('role:owner')->group(function () {
            Route::post('/owner/users/bulk-import', [OwnerUserController::class, 'bulkImport']);
            Route::post('/owner/users/{id}/reset-password', [OwnerUserController::class, 'resetPassword']);
            Route::post('/owner/users/{id}/ban', [OwnerUserController::class, 'ban']);
            Route::post('/owner/users/{id}/unban', [OwnerUserController::class, 'unban']);
            Route::get('/owner/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/owner/export/users', [ExportController::class, 'exportUsers']);
            Route::post('/owner/generate-envelopes', [OwnerUserController::class, 'generateEnvelopeZip']);
            // PERF-15: single aggregate endpoint for the owner analytics screen.
            Route::get('/owner/stats', [OwnerUserController::class, 'stats']);
        });
    });
});
