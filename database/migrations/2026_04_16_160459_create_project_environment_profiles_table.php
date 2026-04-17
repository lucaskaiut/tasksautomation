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
        Schema::create('project_environment_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('validation_profile')->nullable();
            $table->json('environment_definition')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_environment_profiles');
    }
};
