<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add temp_path to resources for the pending -> approved staging flow (F05):
 * student uploads land in storage/temp/{user}/{uuid}.ext and are moved to the
 * permanent /uploads path only when an approver approves the resource.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('resources', 'temp_path')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->text('temp_path')->nullable()->after('file_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('resources', 'temp_path')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->dropColumn('temp_path');
            });
        }
    }
};
