<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarantee messages.deleted_at on every database constellation.
 *
 * The tombstone flow (F07 placeholder UX, D-012) requires this column —
 * MessageController writes it when a sender soft-deletes a message. 000008
 * creates it on fresh installs, but production databases that applied the
 * pre-revision 000014 (whose try/catch swallowed the duplicate-column
 * failure, hiding the drift) may lack it — and 000014 is recorded in their
 * migrations table, so it will never run there again. Idempotent by guard:
 * fresh installs (000008 already created the column) skip this as a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messages') && ! Schema::hasColumn('messages', 'deleted_at')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dateTime('deleted_at')->nullable()->after('is_deleted');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: this migration only closes a schema hole on
        // databases where the column was missing. Dropping it here could
        // destroy tombstone data on databases where it exists legitimately.
    }
};
