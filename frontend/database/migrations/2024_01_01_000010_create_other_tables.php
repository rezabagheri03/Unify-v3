<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_trackers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('specification_id', 32);
            $table->foreign('specification_id')->references('id')->on('course_specifications');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->dateTime('due_date_g');
            $table->string('shamsi_original', 10)->nullable();
            $table->integer('reminder_before_hours')->default(24);
            $table->enum('status', ['pending','submitted','graded','late','missed'])->default('pending');
            $table->text('attachment_path')->nullable();
            $table->float('grade')->nullable();
            $table->string('graded_by', 32)->nullable();
            $table->foreign('graded_by')->references('id')->on('users')->nullOnDelete();
            $table->dateTime('graded_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->boolean('local_notification_scheduled')->default(false);
            $table->timestamps();
        });

        Schema::create('curriculum_charts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('department_id', 32);
            $table->foreign('department_id')->references('id')->on('departments');
            $table->integer('entry_year');
            $table->json('chart_data');
            $table->enum('status', ['draft','pending_approval','approved'])->default('draft');
            $table->string('approver_id', 32)->nullable();
            $table->foreign('approver_id')->references('id')->on('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->unique(['department_id','entry_year','version']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('specification_id', 32);
            $table->foreign('specification_id')->references('id')->on('course_specifications')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer');
            $table->boolean('is_pinned')->default(false);
            $table->string('created_by', 32)->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notice_boards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('specification_id', 32);
            $table->foreign('specification_id')->references('id')->on('course_specifications')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('content');
            $table->enum('priority', ['low','medium','high'])->default('medium');
            $table->string('banner_color', 7)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('created_by', 32)->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('file_path');
            $table->bigInteger('file_size')->unsigned()->nullable();
            $table->string('department_id', 32)->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->boolean('is_university_level')->default(false);
            $table->string('signature_guide', 200);
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->dateTime('start_date_g');
            $table->dateTime('end_date_g');
            $table->string('shamsi_original_start', 10)->nullable();
            $table->string('shamsi_original_end', 10)->nullable();
            $table->enum('event_type', ['registration_open','registration_close','semester_start','semester_end','exam_period_start','exam_period_end','holiday','other']);
            $table->boolean('is_university_wide')->default(false);
            $table->string('department_id', 32)->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->string('color_code', 7)->nullable();
            $table->string('created_by', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
        Schema::dropIfExists('forms');
        Schema::dropIfExists('notice_boards');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('curriculum_charts');
        Schema::dropIfExists('assignment_trackers');
    }
};