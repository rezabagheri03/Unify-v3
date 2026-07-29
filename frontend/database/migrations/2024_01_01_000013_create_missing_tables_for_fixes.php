<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // FIX H3: broadcast_throttles
        Schema::create('broadcast_throttles', function (Blueprint $table) {
            $table->id();
            $table->string('specification_id', 32);
            $table->foreign('specification_id')->references('id')->on('course_specifications')->cascadeOnDelete();
            $table->string('professor_id', 32);
            $table->foreign('professor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime('last_sent_at');
            $table->timestamps();
            $table->unique(['specification_id','professor_id']);
        });

        // FIX H5: download_daily_counts
        Schema::create('download_daily_counts', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->date('date');
            $table->integer('count')->default(0);
            $table->bigInteger('total_bytes')->default(0);
            $table->unique(['student_id','date']);
        });

        // FIX H2: resource_download_logs
        Schema::create('resource_download_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('resource_id');
            $table->foreign('resource_id')->references('id')->on('resources')->cascadeOnDelete();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime('downloaded_at');
            $table->bigInteger('file_size_bytes')->default(0);
            $table->index(['resource_id','downloaded_at']);
            $table->index('student_id');
        });

        // FIX C4: storage_stats (50GB)
        Schema::create('storage_stats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('total_bytes_used')->default(0);
            $table->bigInteger('total_bytes_limit')->default(53687091200); // 50GB
            $table->dateTime('last_calculated_at')->nullable();
            $table->timestamps();
        });

        // FIX M10: notifications type as ENUM (raw for safety)
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
            'spec_changed','resource_new','ticket_answered','ticket_escalated',
            'registration_open_warning','registration_close_warning','exam_period_start',
            'assignment_reminder','assignment_graded','notice_high','general'
        ) NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_stats');
        Schema::dropIfExists('resource_download_logs');
        Schema::dropIfExists('download_daily_counts');
        Schema::dropIfExists('broadcast_throttles');
    }
};