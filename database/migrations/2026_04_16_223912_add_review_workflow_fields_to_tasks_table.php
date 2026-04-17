<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('review_status')->nullable()->after('status');
            $table->unsignedInteger('revision_count')->default(0)->after('review_status');
            $table->timestamp('last_reviewed_at')->nullable()->after('revision_count');
            $table->foreignId('last_reviewed_by')->nullable()->after('last_reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['last_reviewed_by']);
            $table->dropColumn([
                'review_status',
                'revision_count',
                'last_reviewed_at',
                'last_reviewed_by',
            ]);
        });
    }
};
