<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrective migration (DB audit): migration 000013 created notifications.type
 * as a MySQL ENUM that was missing values the application actually inserts
 * (`grace_ended`, `calendar_warning`, plus the semester-transition types used by
 * the semester state machine: registration_open/registration_close/semester_start).
 * Under MySQL strict mode, inserting a missing value aborts with an error,
 * breaking the grace-wipe and calendar crons' notification fan-out.
 *
 * MySQL: widens the ENUM deterministically (fail-fast — no silent catch).
 * SQLite (local/tests): type is a plain VARCHAR there, nothing to do.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'spec_changed','resource_new','ticket_answered','ticket_escalated',
            'registration_open_warning','registration_close_warning','exam_period_start',
            'assignment_reminder','assignment_graded','notice_high','general',
            'grace_ended','calendar_warning',
            'registration_open','registration_close','semester_start'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Note: down() only safe when no rows use the newer values.
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'spec_changed','resource_new','ticket_answered','ticket_escalated',
            'registration_open_warning','registration_close_warning','exam_period_start',
            'assignment_reminder','assignment_graded','notice_high','general'
        ) NOT NULL");
    }
};
