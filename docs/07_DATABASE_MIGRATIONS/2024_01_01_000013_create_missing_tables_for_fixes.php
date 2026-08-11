<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        // FIX H3: Broadcast throttle table for 1 per 10min per professor per spec
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

        // FIX H5: Download daily counts for rate limiting 20 per student per day + fair usage 2TB check
        Schema::create('download_daily_counts', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->date('date');
            $table->integer('count')->default(0);
            $table->bigInteger('total_bytes')->default(0);
            $table->unique(['student_id','date']);
        });

        // FIX H2: Resource download logs for accurate LRU (if last_downloaded_at not enough)
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

        // FIX C4: Clarify storage - add column to SystemConfig will be seeded, but add table for storage stats
        Schema::create('storage_stats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('total_bytes_used')->default(0);
            $table->bigInteger('total_bytes_limit')->default(53687091200); // 50GB default for upgraded plan
            $table->dateTime('last_calculated_at')->nullable();
            $table->timestamps();
        });

        // FIX H1: Idempotency cleanup is via command, no table needed, but add index already exists on expires_at
        // Ensure idempotency_keys has index on expires_at already created in 000011

        // FIX M8: Forms file_size should be NOT NULL - already nullable in migration but we will enforce via validation, keep nullable for backward compat but add check in model
        // FIX M10: Notification type should be ENUM - change from VARCHAR to ENUM for safety
        Schema::table('notifications', function (Blueprint $table) {
            // MySQL doesn't support modifying to ENUM easily if data exists, for new install we will use ENUM via raw statement
            // We'll drop and recreate type column as ENUM via raw
        });
        // Use raw for ENUM change
        \DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('spec_changed','resource_new','ticket_answered','ticket_escalated','registration_open_warning','registration_close_warning','exam_period_start','assignment_reminder','assignment_graded','notice_high','general') NOT NULL");

        // FIX M8: Make forms file_size NOT NULL if possible, keep nullable for now but add default
        // FIX H9: Overnight classes - add is_next_day flag
        Schema::table('course_specifications', function (Blueprint $table) {
            $table->boolean('is_next_day')->default(false)->after('time_end')->comment('If true, time_end is next day, e.g., 22:00-02:00 overnight lab');
        });
    }
    public function down() {
        Schema::dropIfExists('storage_stats');
        Schema::dropIfExists('resource_download_logs');
        Schema::dropIfExists('download_daily_counts');
        Schema::dropIfExists('broadcast_throttles');
        Schema::table('course_specifications', function (Blueprint $table) {
            $table->dropColumn('is_next_day');
        });
    }
};
