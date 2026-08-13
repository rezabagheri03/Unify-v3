<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('course_specifications', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('course_id', 32);
            $table->foreign('course_id')->references('id')->on('courses');
            $table->string('professor_id', 32);
            $table->foreign('professor_id')->references('id')->on('users');
            $table->enum('day_of_week', ['sat','sun','mon','tue','wed','thu','fri']);
            $table->time('time_start');
            $table->time('time_end');
            $table->string('location', 255)->nullable();
            $table->text('telegram_link')->nullable();
            $table->dateTime('exam_date_final_g')->nullable();
            $table->string('shamsi_original_final', 10)->nullable();
            $table->dateTime('exam_date_midterm_g')->nullable();
            $table->string('shamsi_original_midterm', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('semester_id', 32);
            $table->foreign('semester_id')->references('id')->on('semesters');
            $table->timestamps();
            $table->index(['course_id','professor_id']);
            $table->index(['semester_id','is_active']);
            $table->index('professor_id');
            $table->index('day_of_week');
        });
    }
    public function down() { Schema::dropIfExists('course_specifications'); }
};
