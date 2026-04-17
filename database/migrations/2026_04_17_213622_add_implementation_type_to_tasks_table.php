<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('implementation_type', 32)
                ->default('feature')
                ->after('priority');

            $table->index(['project_id', 'implementation_type']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'implementation_type']);
            $table->dropColumn('implementation_type');
        });
    }
};
