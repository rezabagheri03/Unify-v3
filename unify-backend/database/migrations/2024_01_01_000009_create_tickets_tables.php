<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id', 32);
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('department', ['education','technical','student_affairs']);
            $table->string('subject', 255);
            $table->text('description');
            $table->enum('status', ['open','in_progress','answered','closed'])->default('open');
            $table->string('assigned_to', 32)->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->json('student_attachments')->nullable();
            $table->json('staff_attachments')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('escalated_at')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->integer('escalation_level')->default(0);
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->index(['student_id','status']);
            $table->index('is_escalated');
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->string('sender_id', 32);
            $table->foreign('sender_id')->references('id')->on('users');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->dateTime('sent_at');
            $table->boolean('is_staff')->default(false);
            $table->timestamps();
        });

        Schema::create('ticket_daily_counts', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 32);
            $table->date('date');
            $table->integer('count')->default(0);
            $table->unique(['student_id','date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_daily_counts');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
    }
};