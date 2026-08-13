<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Post-audit: audit_logs.action parity between drivers.
 *
 * 000014 widened the action ENUM to VARCHAR(50) on MySQL only. On sqlite
 * (local dev + the whole test suite) the original ENUM survives as a CHECK
 * constraint, so any audit action outside the legacy 13-value list dies with
 * "CHECK constraint failed: action" — exactly the dialect drift the post-plan
 * audit warned about. New privileged-action producers (F-04) need the wider
 * space on BOTH drivers.
 *
 * sqlite cannot ALTER a column/CHECK → rebuild the table (data preserved).
 * MySQL: no-op (already VARCHAR via 000014).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('audit_logs_widened', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 32)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('action', 50); // open vocabulary — was enum-CHECK
            $table->string('resource_type', 50);
            $table->string('resource_id', 100);
            $table->dateTime('timestamp');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('details')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'action']);
            $table->index('resource_type');
        });

        DB::statement('INSERT INTO audit_logs_widened SELECT * FROM audit_logs');
        Schema::drop('audit_logs');
        Schema::rename('audit_logs_widened', 'audit_logs');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Intentionally a no-op: narrowing a vocabulary is never data-safe.
    }
};
