<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FIX C2: academic_status_history for abuse detection
        Schema::create('academic_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('status', ['normal','conditional','gpa_a','final_semester']);
            $table->string('semester_id', 32)->nullable();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->dateTime('declared_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['student_id','status']);
            $table->index('semester_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id','read','created_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['recipient_id','sent_at']);
            $table->index(['specification_id','sent_at']);
        });

        Schema::table('honor_flags', function (Blueprint $table) {
            $table->dateTime('resolved_at')->nullable()->after('resolved');
            $table->string('resolver_id', 32)->nullable()->after('resolved_at');
            $table->foreign('resolver_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('golden_schedule_caches', function (Blueprint $table) {
            $table->index(['student_id','semester_id']);
            $table->index('preferences_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_status_history');
    }
};