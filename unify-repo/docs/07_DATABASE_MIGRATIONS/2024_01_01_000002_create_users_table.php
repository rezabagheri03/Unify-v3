<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 32)->primary(); // Student Number / Personnel ID
            $table->text('password_hash');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->enum('role', ['student','professor','expert','head_of_dept','admin','owner']);
            $table->string('department_id', 32)->nullable();
            $table->foreign('department_id')->references('id')->on('departments');
            $table->enum('academic_status_declared', ['normal','conditional','gpa_a','final_semester'])->nullable();
            $table->dateTime('academic_status_last_declared_at')->nullable();
            $table->integer('academic_status_declaration_count')->default(0);
            $table->boolean('is_honor_system_acknowledged')->default(false);
            $table->boolean('is_banned')->default(false);
            $table->text('banned_reason')->nullable();
            $table->dateTime('banned_at')->nullable();
            $table->string('banned_by', 32)->nullable();
            $table->text('supplementary_details')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('must_change_password')->default(true);
            $table->dateTime('temporary_password_expires_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('last_name_edit_at')->nullable();
            $table->timestamps();
            $table->index('role');
            $table->index('department_id');
            $table->index('is_banned');
        });
        Schema::create('password_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 32);
            $table->text('hash');
            $table->dateTime('created_at');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down() {
        Schema::dropIfExists('password_histories');
        Schema::dropIfExists('users');
    }
};
