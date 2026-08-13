<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('course_id', 32);
            $table->foreign('course_id')->references('id')->on('courses');
            $table->string('professor_id', 32);
            $table->foreign('professor_id')->references('id')->on('users');
            $table->string('specification_id', 32)->nullable();
            $table->foreign('specification_id')->references('id')->on('course_specifications')->nullOnDelete();
            $table->string('uploader_id', 32);
            $table->foreign('uploader_id')->references('id')->on('users');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('file_path')->nullable();
            $table->bigInteger('file_size_bytes')->unsigned();
            $table->string('file_mime', 50);
            $table->string('shamsi_original', 10)->nullable();
            $table->dateTime('created_at_g');
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->integer('version')->default(1);
            $table->uuid('previous_version_id')->nullable();
            $table->foreign('previous_version_id')->references('id')->on('resources')->nullOnDelete();
            // FIX C1: family_id must be nullable initially to avoid chicken-egg FK violation
            // First version: insert with family_id = null, then in Observer created event set family_id = id
            $table->uuid('family_id')->nullable()->index();
            $table->dateTime('scheduled_hard_delete_at')->nullable();
            $table->float('average_rating')->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->dateTime('last_downloaded_at')->nullable(); // FIX H2: LRU tracking for 10GB->50GB upgrade
            $table->enum('badge_type', ['professor','expert_approved','admin_approved'])->nullable();
            $table->boolean('is_superseded')->default(false);
            $table->boolean('is_deleted_content')->default(false);
            $table->boolean('is_protected')->default(false); // professor badge files never auto-evicted
            $table->timestamps();
            $table->index(['course_id','professor_id']);
        });
        Schema::create('resource_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('resource_family_id');
            $table->foreign('resource_family_id')->references('id')->on('resources')->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->dateTime('rated_at');
            $table->boolean('is_self_rating')->default(false);
            $table->timestamps();
            $table->unique(['student_id','resource_family_id']);
        });
        Schema::create('resource_sticky_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('resource_family_id');
            $table->foreign('resource_family_id')->references('id')->on('resources')->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();
            $table->unique(['student_id','resource_family_id']);
        });
        Schema::create('resource_upload_counts', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 32);
            $table->date('date');
            $table->integer('count')->default(0);
            $table->unique(['user_id','date']);
        });
    }
    public function down() {
        Schema::dropIfExists('resource_upload_counts');
        Schema::dropIfExists('resource_sticky_notes');
        Schema::dropIfExists('resource_ratings');
        Schema::dropIfExists('resources');
    }
};
