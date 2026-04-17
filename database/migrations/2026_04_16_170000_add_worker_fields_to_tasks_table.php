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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('claimed_by_worker')->nullable()->after('priority');
            $table->timestamp('claimed_at')->nullable()->after('claimed_by_worker');
            $table->timestamp('started_at')->nullable()->after('claimed_at');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->timestamp('last_heartbeat_at')->nullable()->after('finished_at');
            $table->unsignedInteger('attempts')->default(0)->after('last_heartbeat_at');
            $table->unsignedInteger('max_attempts')->default(3)->after('attempts');
            $table->timestamp('locked_until')->nullable()->after('max_attempts');
            $table->text('failure_reason')->nullable()->after('locked_until');
            $table->longText('execution_summary')->nullable()->after('failure_reason');
            $table->timestamp('run_after')->nullable()->after('execution_summary');

            $table->index(['status', 'priority', 'run_after']);
            $table->index('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority', 'run_after']);
            $table->dropIndex(['locked_until']);

            $table->dropColumn([
                'claimed_by_worker',
                'claimed_at',
                'started_at',
                'finished_at',
                'last_heartbeat_at',
                'attempts',
                'max_attempts',
                'locked_until',
                'failure_reason',
                'execution_summary',
                'run_after',
            ]);
        });
    }
};

