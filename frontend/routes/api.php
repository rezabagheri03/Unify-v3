<?php

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
use App\Http\Controllers\Api\Curriculum\CurriculumChartController;
use App\Http\Controllers\Api\Admin\ResourceApprovalController;
use App\Http\Controllers\Api\Admin\SemesterController;
use App\Http\Controllers\Api\Owner\UserController as OwnerUserController;
use App\Http\Controllers\Api\Owner\AuditLogController;
use App\Http\Controllers\Api\Excel\ExportController;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Controllers\Api\BroadcastThrottleController;
use App\Http\Controllers\Api\OfflineSyncController;

// Public
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::get('/health', [\App\Http\Controllers\Api\HealthController::class, 'check']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/onboarding', [AuthController::class, 'onboarding']);
    Route::post('/password/change', [AuthController::class, 'changePassword']);
    Route::get('/users/me', fn (Request $request) => $request->user());

    // Honor
    Route::get('/users/me/academic-status', [UserController::class, 'getAcademicStatus']);
    Route::post('/users/me/academic-status', [UserController::class, 'declareAcademicStatus']);

    // Scheduler
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/specifications', [SpecificationController::class, 'index']);
        Route::get('/enrollment/temp', [EnrollmentController::class, 'indexTemp']);
        Route::post('/enrollment/temp', [EnrollmentController::class, 'storeTemp']);
        Route::post('/enrollment/final', [EnrollmentController::class, 'finalize']);
        Route::get('/golden-schedule', [GoldenScheduleController::class, 'generate']);
    });

    // Resources
    Route::middleware('throttle:20,1')->group(function () {
        Route::get('/resources', [ResourceController::class, 'index']);
        Route::post('/resources/upload', [ResourceController::class, 'upload']);
        Route::get('/resources/{id}/download', [ResourceController::class, 'download']);
        Route::post('/resources/{id}/rating', [RatingController::class, 'store']);
        Route::get('/resources/{id}/sticky-note', [StickyNoteController::class, 'show']);
        Route::post('/resources/{id}/sticky-note', [StickyNoteController::class, 'store']);
    });

    // Messaging & Tickets
    Route::middleware('throttle:15,1')->group(function () {
        Route::get('/messages', [MessageController::class, 'index']);
        Route::post('/messages/send', [MessageController::class, 'send']);
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);
    });

    // Notifications (Polling)
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/devices', [DeviceController::class, 'register']);
    Route::post('/notifications/mute', [NotificationController::class, 'mute']);
    Route::post('/broadcast/check', [BroadcastThrottleController::class, 'check']);
    Route::post('/admin/branding/logo', [BrandingController::class, 'uploadLogo']);
    Route::post('/offline/sync', [OfflineSyncController::class, 'sync']);

    // Assignment & Curriculum
    Route::middleware('throttle:25,1')->group(function () {
        Route::get('/assignments', [AssignmentController::class, 'index']);
        Route::post('/assignments', [AssignmentController::class, 'store']);
        Route::get('/curriculum', [CurriculumChartController::class, 'index']);
    });

    // Admin
    Route::middleware('role:expert,admin')->group(function () {
        Route::get('/admin/resources/pending', [ResourceApprovalController::class, 'pending']);
        Route::post('/admin/resources/{id}/approve', [ResourceApprovalController::class, 'approve']);
        Route::post('/admin/semesters', [SemesterController::class, 'createNewSemester']);
    });

    // Owner
    Route::middleware('role:owner')->group(function () {
        Route::post('/owner/users/bulk-import', [OwnerUserController::class, 'bulkImport']);
        Route::get('/owner/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/owner/export/users', [ExportController::class, 'exportUsers']);
        Route::post('/owner/generate-envelopes', [OwnerUserController::class, 'generateEnvelopeZip']);
    });
});