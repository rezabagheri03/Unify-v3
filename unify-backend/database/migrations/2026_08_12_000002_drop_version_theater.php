<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TODO-042: remove optimistic-LOCK theater columns.
 *
 * Audit verdict (read-only source audit, 2026-08-12): the API has ZERO
 * concurrent-content-edit routes — no PATCH/PUT exists for enrollments,
 * resources, tickets or curriculum_charts — so no table needs an
 * optimistic-locking `version` column.
 *
 *   enrollments.version — set to 1 on create, +1 on finalize, never read.
 *     Concurrency is already handled pessimistically (lockForUpdate in the
 *     enrollment transaction). Pure theater: dropped.
 *   tickets.version — never written, not fillable, inert: dropped.
 *
 * Business revisions are NOT dropped: resources.version (shown in the UI,
 * drives the F06 version chain) and curriculum_charts.version (part of the
 * unique (department_id, entry_year, version) revision key) are domain data,
 * not lock tokens. See docs/DECISIONS.md.
 *
 * Guards make this safe on any constellation (fresh, prod, re-run).
 */
return new class extends Migration
{
    private array $theaterTables = ['enrollments', 'tickets'];

    public function up(): void
    {
        foreach ($this->theaterTables as $tbl) {
            if (Schema::hasColumn($tbl, 'version')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropColumn('version');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->theaterTables as $tbl) {
            if (! Schema::hasColumn($tbl, 'version')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->unsignedInteger('version')->default(1);
                });
            }
        }
    }
};
