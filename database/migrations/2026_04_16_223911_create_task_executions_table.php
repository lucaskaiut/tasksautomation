<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('worker_id')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('summary')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('logs_path')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('commit_sha')->nullable();
            $table->string('pull_request_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'finished_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_executions');
    }
};
