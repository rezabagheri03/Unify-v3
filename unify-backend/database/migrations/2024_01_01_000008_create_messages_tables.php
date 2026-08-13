<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sender_id', 32);
            $table->foreign('sender_id')->references('id')->on('users');
            $table->string('recipient_id', 32)->nullable();
            $table->foreign('recipient_id')->references('id')->on('users')->nullOnDelete();
            $table->string('specification_id', 32)->nullable();
            $table->foreign('specification_id')->references('id')->on('course_specifications')->nullOnDelete();
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->dateTime('sent_at');
            $table->boolean('is_edited')->default(false);
            $table->dateTime('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->dateTime('deleted_at')->nullable();
            $table->uuid('parent_message_id')->nullable();
            $table->foreign('parent_message_id')->references('id')->on('messages')->nullOnDelete();
            $table->enum('priority', ['low','normal','high'])->default('normal');
            $table->timestamps();
            $table->index('recipient_id');
            $table->index('specification_id');
            $table->index('sent_at');
        });

        Schema::create('message_read_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->string('user_id', 32);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->timestamps();
            $table->unique(['message_id','user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_read_status');
        Schema::dropIfExists('messages');
    }
};