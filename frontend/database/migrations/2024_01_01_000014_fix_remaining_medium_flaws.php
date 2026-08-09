<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // FIX M1: Convert ENUMs to VARCHAR for future-proofing
        try { DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(32) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE users MODIFY COLUMN academic_status_declared VARCHAR(32) NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE semesters MODIFY COLUMN global_state VARCHAR(20) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE course_specifications MODIFY COLUMN day_of_week VARCHAR(10) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE enrollments MODIFY COLUMN status VARCHAR(20) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE enrollments MODIFY COLUMN academic_status_at_enrollment VARCHAR(32) NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE resources MODIFY COLUMN status VARCHAR(20) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE resources MODIFY COLUMN badge_type VARCHAR(30) NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE messages MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal'"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE tickets MODIFY COLUMN department VARCHAR(30) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE tickets MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE assignment_trackers MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE curriculum_charts MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE notice_boards MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'medium'"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE academic_calendars MODIFY COLUMN event_type VARCHAR(30) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(50) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE notifications MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'low'"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE device_tokens MODIFY COLUMN provider VARCHAR(20) NOT NULL"); } catch (\Exception $e) {}
        try { DB::statement("ALTER TABLE device_tokens MODIFY COLUMN platform VARCHAR(20) NOT NULL"); } catch (\Exception $e) {}

        // FIX M2: Soft deletes
        try {
            Schema::table('courses', function (Blueprint $table) {
                $table->softDeletes()->after('is_active');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('course_specifications', function (Blueprint $table) {
                $table->softDeletes()->after('is_active');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('resources', function (Blueprint $table) {
                $table->softDeletes()->after('is_deleted_content');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('messages', function (Blueprint $table) {
                $table->softDeletes()->after('is_deleted');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->softDeletes()->after('status');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('forms', function (Blueprint $table) {
                $table->softDeletes()->after('is_active');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('academic_calendars', function (Blueprint $table) {
                $table->softDeletes();
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('notice_boards', function (Blueprint $table) {
                $table->softDeletes();
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('faqs', function (Blueprint $table) {
                $table->softDeletes();
            });
        } catch (\Exception $e) {}

        // FIX M4: Lower index for course code
        try {
            DB::statement("CREATE UNIQUE INDEX courses_code_lower_unique ON courses ((LOWER(code)))");
        } catch (\Exception $e) {}

        // FIX M8: forms file_size NOT NULL
        try { DB::statement("ALTER TABLE forms MODIFY COLUMN file_size BIGINT UNSIGNED NOT NULL DEFAULT 0"); } catch (\Exception $e) {}

        // FIX M9: DeviceToken provider only pushe/web_push
        try { DB::statement("ALTER TABLE device_tokens MODIFY COLUMN provider ENUM('pushe','web_push') NOT NULL"); } catch (\Exception $e) {}

        // FIX M3: Additional composite indexes
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(['student_id', 'status', 'semester_id'], 'enrollments_student_status_semester_idx');
            });
        } catch (\Exception $e) {}

        try {
            Schema::create('course_specification_history', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('original_id', 32);
                $table->string('course_id', 32);
                $table->string('professor_id', 32);
                $table->string('day_of_week', 10);
                $table->time('time_start');
                $table->time('time_end');
                $table->string('location', 255)->nullable();
                $table->string('reason_deleted', 500)->nullable();
                $table->string('deleted_by', 32)->nullable();
                $table->dateTime('deleted_at');
                $table->timestamps();
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('course_specification_history');
    }
};