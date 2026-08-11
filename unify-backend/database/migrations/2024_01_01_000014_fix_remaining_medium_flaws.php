<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FIX M1/M2/M3/M4/M8/M9 — deterministic version (DB audit / TODO-006).
 *
 * The previous revision wrapped every statement in `try { } catch (\Exception $e) {}`,
 * silently swallowing failures and letting production and test schemas drift apart
 * (e.g. an ENUM→VARCHAR conversion that failed on prod was invisible).
 *
 * Revision policy:
 *  - Raw ALTERs run ONLY on MySQL (SQLite keeps the earlier STRING columns, which
 *    is acceptable for local/tests and matches 000013's documented approach).
 *  - No statement may fail silently: exceptions propagate and abort the migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';

        // FIX M1: Convert ENUMs to VARCHAR for future-proofing (MySQL only)
        if ($isMySql) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(32) NOT NULL");
            DB::statement("ALTER TABLE users MODIFY COLUMN academic_status_declared VARCHAR(32) NULL");
            DB::statement("ALTER TABLE semesters MODIFY COLUMN global_state VARCHAR(20) NOT NULL");
            DB::statement("ALTER TABLE course_specifications MODIFY COLUMN day_of_week VARCHAR(10) NOT NULL");
            DB::statement("ALTER TABLE enrollments MODIFY COLUMN status VARCHAR(20) NOT NULL");
            DB::statement("ALTER TABLE enrollments MODIFY COLUMN academic_status_at_enrollment VARCHAR(32) NULL");
            DB::statement("ALTER TABLE resources MODIFY COLUMN status VARCHAR(20) NOT NULL");
            DB::statement("ALTER TABLE resources MODIFY COLUMN badge_type VARCHAR(30) NULL");
            DB::statement("ALTER TABLE messages MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN department VARCHAR(30) NOT NULL");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'");
            DB::statement("ALTER TABLE assignment_trackers MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE curriculum_charts MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE notice_boards MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'medium'");
            DB::statement("ALTER TABLE academic_calendars MODIFY COLUMN event_type VARCHAR(30) NOT NULL");
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE notifications MODIFY COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE device_tokens MODIFY COLUMN platform VARCHAR(20) NOT NULL");

            // FIX M9: DeviceToken provider only pushe/web_push
            DB::statement("ALTER TABLE device_tokens MODIFY COLUMN provider ENUM('pushe','web_push') NOT NULL");

            // FIX M8: forms file_size NOT NULL
            DB::statement("ALTER TABLE forms MODIFY COLUMN file_size BIGINT UNSIGNED NOT NULL DEFAULT 0");

            // FIX M4: case-insensitive course code uniqueness. MySQL's
            // utf8mb4_unicode_ci is already case-insensitive, so this only
            // matters for SQLite — implemented per-driver, fail-fast.
            DB::statement("CREATE UNIQUE INDEX courses_code_lower_unique ON courses ((LOWER(code)))");
        } else {
            DB::statement("CREATE UNIQUE INDEX courses_code_lower_unique ON courses (LOWER(code))");
        }

        // FIX M2: Soft deletes (portable — Schema builder)
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

        // FIX M3: Additional composite index
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'semester_id'], 'enrollments_student_status_semester_idx');
        });

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
    }

    public function down(): void
    {
        Schema::dropIfExists('course_specification_history');
    }
};
