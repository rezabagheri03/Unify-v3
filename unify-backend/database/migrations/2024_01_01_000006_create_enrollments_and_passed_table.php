<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_passed_courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('course_id', 32);
            $table->foreign('course_id')->references('id')->on('courses');
            $table->boolean('passed')->default(false);
            $table->float('grade')->nullable();
            $table->integer('entry_year')->nullable();
            $table->timestamps();
            $table->unique(['student_id','course_id','entry_year']);
            $table->index(['student_id','entry_year']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('specification_id', 32);
            $table->foreign('specification_id')->references('id')->on('course_specifications')->cascadeOnDelete();
            $table->string('semester_id', 32);
            $table->foreign('semester_id')->references('id')->on('semesters');
            $table->enum('status', ['temporary','finalized','archived'])->default('temporary');
            // FIX C2: academic_status_at_enrollment for abuse detection
            $table->enum('academic_status_at_enrollment', ['normal','conditional','gpa_a','final_semester'])->nullable();
            $table->dateTime('enrolled_at');
            $table->dateTime('finalized_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->unique(['student_id','specification_id','semester_id']);
            $table->index(['student_id','semester_id']);
            $table->index(['student_id','status','semester_id']); // FIX M3
        });

        Schema::create('golden_schedule_caches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->string('semester_id', 32);
            $table->string('preferences_hash', 64);
            $table->json('combos');
            $table->dateTime('generated_at');
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golden_schedule_caches');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('student_passed_courses');
    }
};