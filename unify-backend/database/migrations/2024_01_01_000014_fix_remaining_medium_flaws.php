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
            // MariaDB (post-audit F-05): functional key parts ((...)) are
            // rejected with SQL 1064 — and the collation ALREADY guarantees
            // case-insensitive uniqueness on utf8mb4, so skip it there.
            $serverVersion = strtolower((string) (DB::selectOne('select version() as v')->v ?? ''));
            if (! str_contains($serverVersion, 'mariadb')) {
                DB::statement("CREATE UNIQUE INDEX courses_code_lower_unique ON courses ((LOWER(code)))");
            }
        } else {
            DB::statement("CREATE UNIQUE INDEX courses_code_lower_unique ON courses (LOWER(code))");
        }

        // FIX M2: Soft-delete timestamp columns (guarded, idempotent).
        //
        // D-012 retired the SoftDeletes trait from every model, so these are
        // legacy columns kept for prod parity (production DBs already grew
        // them under the pre-revision 000014). messages.deleted_at is the one
        // LIVE column: MessageController writes it for tombstones, and 000008
        // creates it directly — which is why the unguarded version of this
        // block crashed fresh MySQL installs with 42S21/1060 "Duplicate column
        // name 'deleted_at'" (the pre-revision try/catch swallowed that same
        // error on prod, hiding the drift). The hasColumn guards make this
        // block safe on every constellation: fresh (000008 already created
        // messages.deleted_at), prod-upgrade, and re-run. DBs that applied the
        // pre-revision 000014 and therefore never re-run it are covered by
        // 2026_08_12_000001_ensure_messages_deleted_at.
        $softDeleteAdds = [
            'courses'               => 'is_active',
            'course_specifications' => 'is_active',
            'resources'             => 'is_deleted_content',
            'messages'              => 'is_deleted',
            'tickets'               => 'status',
            'forms'                 => 'is_active',
            'academic_calendars'    => null,
            'notice_boards'         => null,
            'faqs'                  => null,
        ];
        foreach ($softDeleteAdds as $tbl => $after) {
            if (! Schema::hasTable($tbl) || Schema::hasColumn($tbl, 'deleted_at')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) use ($after) {
                if ($after === null) {
                    $table->softDeletes();
                } else {
                    $table->softDeletes()->after($after);
                }
            });
        }

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
