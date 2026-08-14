<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow class notices and FAQs to be general (no specification_id):
 * F13 notice board / FAQ rows may be university-wide or per-spec.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notice_boards', function (Blueprint $table) {
            $table->string('specification_id', 32)->nullable()->change();
        });
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('specification_id', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notice_boards', function (Blueprint $table) {
            $table->string('specification_id', 32)->nullable(false)->change();
        });
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('specification_id', 32)->nullable(false)->change();
        });
    }
};
