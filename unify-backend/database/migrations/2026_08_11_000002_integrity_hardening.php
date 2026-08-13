<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * DB-integrity hardening (Database audit / TODO-029+030+032):
 *
 *  1) Missing FKs added: users.banned_by, forms.created_by,
 *     academic_calendars.created_by (all nullable -> nullOnDelete).
 *  2) NULL-hole in the passed-courses uniqueness closed: the unique index on
 *     (student_id, course_id, entry_year) let unlimited NULL-entry_year
 *     duplicates through; replaced by a normalized expression unique index.
 *  3) messages.sender_id becomes nullable + nullOnDelete so a user purge does
 *     not violate the FK (was implicit RESTRICT). Message history survives.
 *  4) CHECK constraints for grade/rating ranges (MySQL 8.0.16+ only).
 *  5) FLOAT grade columns -> DECIMAL (MySQL only; SQLite affinity is loose,
 *     so the change is a documented no-op there).
 *  6) Dead tables dropped: storage_stats (the stats writer targets
 *     system_configs instead) and course_specification_history (no writers
 *     anywhere in the codebase).
 *
 * SQLite (local/tests) skips the ALTER-only operations — documented drift,
 * consistent with the 000013/000014 approach.
 */
return new class extends Migration
{
    public function up(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';

        // 1) FKs (portable)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('banned_by')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('forms', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // 2) Normalized uniqueness for passed courses
        if ($isMySql) {
            DB::statement("ALTER TABLE student_passed_courses ADD COLUMN entry_year_norm SMALLINT GENERATED ALWAYS AS (COALESCE(entry_year, 0)) STORED");
            DB::statement("ALTER TABLE student_passed_courses DROP INDEX student_passed_courses_student_id_course_id_entry_year_unique");
            DB::statement("ALTER TABLE student_passed_courses ADD UNIQUE KEY passed_unique_norm (student_id, course_id, entry_year_norm)");
        } else {
            DB::statement("DROP INDEX IF EXISTS student_passed_courses_student_id_course_id_entry_year_unique");
            DB::statement("CREATE UNIQUE INDEX passed_unique_norm ON student_passed_courses (student_id, course_id, COALESCE(entry_year, 0))");
        }

        if ($isMySql) {
            // 3) sender FK -> nullable + nullOnDelete (purge-safe)
            DB::statement("ALTER TABLE messages MODIFY COLUMN sender_id VARCHAR(32) NULL");
            DB::statement("ALTER TABLE messages DROP FOREIGN KEY messages_sender_id_foreign");
            DB::statement("ALTER TABLE messages ADD CONSTRAINT messages_sender_id_foreign FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE SET NULL");

            // 4) Range CHECKs (MySQL 8.0.16+)
            DB::statement("ALTER TABLE student_passed_courses ADD CONSTRAINT chk_grade_range CHECK (grade IS NULL OR (grade >= 0 AND grade <= 20))");
            DB::statement("ALTER TABLE resource_ratings ADD CONSTRAINT chk_rating_range CHECK (rating BETWEEN 1 AND 5)");

            // 5) FLOAT -> DECIMAL
            DB::statement("ALTER TABLE student_passed_courses MODIFY COLUMN grade DECIMAL(5,2) NULL");
            DB::statement("ALTER TABLE resources MODIFY COLUMN average_rating DECIMAL(3,2) NOT NULL DEFAULT 0");
        }

        // 6) Dead tables (portable)
        Schema::dropIfExists('storage_stats');
        Schema::dropIfExists('course_specification_history');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE messages DROP FOREIGN KEY messages_sender_id_foreign");
            DB::statement("ALTER TABLE messages MODIFY COLUMN sender_id VARCHAR(32) NOT NULL");
            DB::statement("ALTER TABLE messages ADD CONSTRAINT messages_sender_id_foreign FOREIGN KEY (sender_id) REFERENCES users (id)");
            DB::statement("ALTER TABLE student_passed_courses DROP INDEX passed_unique_norm");
            DB::statement("ALTER TABLE student_passed_courses DROP COLUMN entry_year_norm");
            DB::statement("ALTER TABLE student_passed_courses ADD UNIQUE KEY student_passed_courses_student_id_course_id_entry_year_unique (student_id, course_id, entry_year)");
            DB::statement("ALTER TABLE student_passed_courses DROP CHECK chk_grade_range");
            DB::statement("ALTER TABLE resource_ratings DROP CHECK chk_rating_range");
        } else {
            DB::statement("DROP INDEX IF EXISTS passed_unique_norm");
        }

        Schema::table('users', fn (Blueprint $table) => $table->dropForeign(['banned_by']));
        Schema::table('forms', fn (Blueprint $table) => $table->dropForeign(['created_by']));
        Schema::table('academic_calendars', fn (Blueprint $table) => $table->dropForeign(['created_by']));
    }
};
