<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 32);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('token');
            $table->enum('provider', ['fcm','pushe','web_push']);
            $table->enum('platform', ['web','android']);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 32)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->enum('action', [
                'deletion','major_edit','password_reset','role_change','ban',
                'honor_status_change','final_semester_abuse_flag','login','failed_login',
                'file_upload','file_approval','message_edit_delete','ticket_status_change'
            ]);
            $table->string('resource_type', 50);
            $table->string('resource_id', 100);
            $table->dateTime('timestamp');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('details')->nullable(); // encrypted
            $table->boolean('is_suspicious')->default(false);
            $table->timestamps();
            $table->index(['user_id','action']);
            $table->index('resource_type');
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 36)->unique();
            $table->string('user_id', 32);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->integer('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('expires_at');
            $table->index('expires_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id', 32);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('title', 255);
            $table->text('body');
            $table->json('data')->nullable();
            $table->enum('priority', ['critical','high','low'])->default('low');
            $table->boolean('read')->default(false);
            $table->dateTime('created_at');
            $table->index(['user_id','read']);
        });

        Schema::create('notification_mutes', function (Blueprint $table) {
            $table->string('user_id', 32);
            $table->string('specification_id', 32);
            $table->boolean('muted')->default(false);
            $table->primary(['user_id','specification_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('specification_id')->references('id')->on('course_specifications')->cascadeOnDelete();
        });

        Schema::create('system_configs', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('honor_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('flag_type', 50);
            $table->integer('count')->default(0);
            $table->dateTime('last_declared_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->text('resolve_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('honor_flags');
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('notification_mutes');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('device_tokens');
    }
};