<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('current_stage', 64)
                ->default('analysis')
                ->after('implementation_type');

            $table->string('analysis_domain', 32)->nullable()->after('current_stage');
            $table->decimal('analysis_confidence', 4, 2)->nullable()->after('analysis_domain');
            $table->string('analysis_next_stage', 64)->nullable()->after('analysis_confidence');
            $table->longText('analysis_summary')->nullable()->after('analysis_next_stage');
            $table->json('analysis_evidence')->nullable()->after('analysis_summary');
            $table->json('analysis_risks')->nullable()->after('analysis_evidence');
            $table->json('analysis_artifacts')->nullable()->after('analysis_risks');
            $table->longText('analysis_notes')->nullable()->after('analysis_artifacts');

            $table->string('stage_execution_reference')->nullable()->after('analysis_notes');
            $table->string('stage_execution_stage', 64)->nullable()->after('stage_execution_reference');
            $table->string('stage_execution_status', 64)->nullable()->after('stage_execution_stage');
            $table->string('stage_execution_agent')->nullable()->after('stage_execution_status');
            $table->longText('stage_execution_summary')->nullable()->after('stage_execution_agent');
            $table->json('stage_execution_output')->nullable()->after('stage_execution_summary');
            $table->longText('stage_execution_raw_output')->nullable()->after('stage_execution_output');
            $table->integer('stage_execution_exit_code')->nullable()->after('stage_execution_raw_output');
            $table->timestamp('stage_execution_started_at')->nullable()->after('stage_execution_exit_code');
            $table->timestamp('stage_execution_finished_at')->nullable()->after('stage_execution_started_at');
            $table->json('stage_execution_context')->nullable()->after('stage_execution_finished_at');

            $table->string('handoff_from_stage', 64)->nullable()->after('stage_execution_context');
            $table->string('handoff_to_stage', 64)->nullable()->after('handoff_from_stage');
            $table->longText('handoff_reason')->nullable()->after('handoff_to_stage');
            $table->decimal('handoff_confidence', 4, 2)->nullable()->after('handoff_reason');
            $table->longText('handoff_summary')->nullable()->after('handoff_confidence');
            $table->json('handoff_payload')->nullable()->after('handoff_summary');

            $table->index('current_stage');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['current_stage']);

            $table->dropColumn([
                'current_stage',
                'analysis_domain',
                'analysis_confidence',
                'analysis_next_stage',
                'analysis_summary',
                'analysis_evidence',
                'analysis_risks',
                'analysis_artifacts',
                'analysis_notes',
                'stage_execution_reference',
                'stage_execution_stage',
                'stage_execution_status',
                'stage_execution_agent',
                'stage_execution_summary',
                'stage_execution_output',
                'stage_execution_raw_output',
                'stage_execution_exit_code',
                'stage_execution_started_at',
                'stage_execution_finished_at',
                'stage_execution_context',
                'handoff_from_stage',
                'handoff_to_stage',
                'handoff_reason',
                'handoff_confidence',
                'handoff_summary',
                'handoff_payload',
            ]);
        });
    }
};
