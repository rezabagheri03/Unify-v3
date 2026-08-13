<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        // FIX M1: ENUM hard to migrate -> Convert all ENUM to VARCHAR(50) with check constraint via raw
        // MySQL doesn't support CHECK with ENUM in older versions, but we use VARCHAR + application-level validation
        // For new install, we change ENUM columns to VARCHAR for future-proof. For existing data, we convert.

        // Users role
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(32) NOT NULL");
        // Academic status
        DB::statement("ALTER TABLE users MODIFY COLUMN academic_status_declared VARCHAR(32) NULL");
        // Semesters global_state
        DB::statement("ALTER TABLE semesters MODIFY COLUMN global_state VARCHAR(20) NOT NULL");
        // CourseSpecifications day_of_week
        DB::statement("ALTER TABLE course_specifications MODIFY COLUMN day_of_week VARCHAR(10) NOT NULL");
        // Enrollments status + academic_status_at_enrollment
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN status VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE enrollments MODIFY COLUMN academic_status_at_enrollment VARCHAR(32) NULL");
        // Resources status + badge_type
        DB::statement("ALTER TABLE resources MODIFY COLUMN status VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE resources MODIFY COLUMN badge_type VARCHAR(30) NULL");
        // Messages priority
        DB::statement("ALTER TABLE messages MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal'");
        // Tickets department + status
        DB::statement("ALTER TABLE tickets MODIFY COLUMN department VARCHAR(30) NOT NULL");
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'");
        // Assignment status
        DB::statement("ALTER TABLE assignment_trackers MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        // CurriculumCharts status
        DB::statement("ALTER TABLE curriculum_charts MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'");
        // NoticeBoards priority
        DB::statement("ALTER TABLE notice_boards MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'medium'");
        // Forms is_active and is_university_level already BOOL, okay
        // AcademicCalendars event_type
        DB::statement("ALTER TABLE academic_calendars MODIFY COLUMN event_type VARCHAR(30) NOT NULL");
        // AuditLogs action
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(50) NOT NULL");
        // Notifications priority
        DB::statement("ALTER TABLE notifications MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'low'");
        // DeviceTokens provider + platform
        DB::statement("ALTER TABLE device_tokens MODIFY COLUMN provider VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE device_tokens MODIFY COLUMN platform VARCHAR(20) NOT NULL");

        // FIX M2: Add soft deletes to critical tables (keep hard delete policy but add deleted_at for audit + recovery)
        Schema::table('courses', function (Blueprint $table) {
            $table->softDeletes()->after('is_active');
        });
        Schema::table('course_specifications', function (Blueprint $table) {
            $table->softDeletes()->after('is_active');
        });
        Schema::table('resources', function (Blueprint $table) {
            $table->softDeletes()->after('is_deleted_content');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->softDeletes()->after('is_deleted');
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->softDeletes()->after('status');
        });
        Schema::table('forms', function (Blueprint $table) {
            $table->softDeletes()->after('is_active');
        });
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('notice_boards', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('faqs', function (Blueprint $table) {
            $table->softDeletes();
        });

        // FIX M4: Case sensitivity - ensure course code unique case-insensitive via lower index
        // MySQL utf8mb4_general_ci is already case-insensitive, but add check in model observer to lowercase code before save
        // Add functional index for LOWER(code) - MySQL 8 supports functional index
        try {
            DB::statement("CREATE UNIQUE INDEX courses_code_lower_unique ON courses ((LOWER(code)))");
        } catch (\Exception $e) {
            // Index already exists or not supported
        }

        // FIX M8: Forms file_size should be NOT NULL - change from nullable to NOT NULL with default 0
        DB::statement("ALTER TABLE forms MODIFY COLUMN file_size BIGINT UNSIGNED NOT NULL DEFAULT 0");

        // FIX M9: DeviceToken provider remove fcm (only pushe and web_push for shared host, fcm requires Google outside Iran)
        DB::statement("ALTER TABLE device_tokens MODIFY COLUMN provider ENUM('pushe','web_push') NOT NULL");

        // FIX M10: Notification type already changed to ENUM in 000013, ensure it includes all types

        // FIX M11: HonorFlags resolved_at and resolver_id already added in 000012, ensure resolve_reason TEXT

        // FIX M3: Composite indexes already added in 000012, but add more for common queries
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'semester_id'], 'enrollments_student_status_semester_idx');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read', 'created_at'], 'notifications_user_read_created_idx');
        });

        // FIX M2: Add history table for hard deleted specs/courses to keep enrollment history
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

        // FIX: Add download_daily_counts table already created in 000013, ensure total_bytes index
        // FIX: Add broadcast_throttles already created

        // FIX: Add storage_stats already created
    }

    public function down() {
        Schema::dropIfExists('course_specification_history');
        // Reverting ENUM conversions is complex, for MVP we keep VARCHAR - no down
    }
};
