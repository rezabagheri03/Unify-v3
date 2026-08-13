<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Round-2 (audit V2-02 + product decision "build expert lanes"):
 * staff ownership of a functional ticket lane. Plain VARCHAR (not ENUM) per
 * the 000014 dialect-parity doctrine — valid values are enforced at the
 * application layer: education | technical | student_affairs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ticket_lane', 30)->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ticket_lane');
        });
    }
};
