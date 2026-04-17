<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('environment_profile_id')
                ->nullable()
                ->constrained('project_environment_profiles')
                ->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->longText('description');
            $table->longText('deliverables')->nullable();
            $table->longText('constraints')->nullable();

            $table->string('status', 32);
            $table->string('priority', 32);
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
