<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('courses', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('code', 32)->unique();
            $table->text('name');
            $table->tinyInteger('credits')->unsigned(); // 0-6
            $table->string('department_id', 32);
            $table->foreign('department_id')->references('id')->on('departments');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->string('course_id', 32);
            $table->string('prerequisite_id', 32);
            $table->primary(['course_id','prerequisite_id']);
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('prerequisite_id')->references('id')->on('courses')->cascadeOnDelete();
        });
        Schema::create('course_corequisites', function (Blueprint $table) {
            $table->string('course_id', 32);
            $table->string('corequisite_id', 32);
            $table->primary(['course_id','corequisite_id']);
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('corequisite_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }
    public function down() {
        Schema::dropIfExists('course_corequisites');
        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('courses');
    }
};
