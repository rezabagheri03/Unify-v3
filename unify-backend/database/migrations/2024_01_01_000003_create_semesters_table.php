<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('name', 100);
            $table->boolean('is_current')->default(false)->index();
            $table->enum('global_state', ['enrolling','active','exam'])->default('enrolling');
            $table->dateTime('start_date_g')->nullable();
            $table->dateTime('end_date_g')->nullable();
            $table->string('shamsi_original_start', 10)->nullable();
            $table->string('shamsi_original_end', 10)->nullable();
            $table->dateTime('grace_period_ends_at')->nullable();
            $table->boolean('grace_period_handled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};