<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_execution_id')->constrained('task_executions')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('decision');
            $table->text('notes')->nullable();
            $table->text('current_behavior')->nullable();
            $table->text('expected_behavior')->nullable();
            $table->text('preserve_scope')->nullable();
            $table->timestamps();

            $table->unique('task_execution_id');
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reviews');
    }
};
