<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('stage', 64);
            $table->longText('summary');
            $table->timestamps();
            $table->index(['task_id', 'id']);
        });

        if (! Schema::hasColumn('tasks', 'analysis_domain')) {
            $this->seedNewInstallations();

            return;
        }

        $this->backfillFromLegacyTasks();
        $this->dropLegacyColumns();
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_stage_histories')) {
            return;
        }

        Schema::drop('task_stage_histories');

        if (! Schema::hasColumn('tasks', 'current_stage')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
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
        });
    }

    private function seedNewInstallations(): void
    {
        $now = now()->toDateTimeString();
        $taskIds = DB::table('tasks')->pluck('id');
        foreach ($taskIds as $id) {
            $stage = (string) DB::table('tasks')->where('id', $id)->value('current_stage');
            DB::table('task_stage_histories')->insert([
                'task_id' => $id,
                'stage' => $stage !== '' ? $stage : 'analysis',
                'summary' => 'Tarefa criada',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillFromLegacyTasks(): void
    {
        $tasks = DB::table('tasks')->orderBy('id')->get();
        $now = now()->toDateTimeString();

        foreach ($tasks as $row) {
            $parts = array_filter([
                is_string($row->analysis_summary ?? null) && $row->analysis_summary !== '' ? $row->analysis_summary : null,
                is_string($row->stage_execution_summary ?? null) && $row->stage_execution_summary !== '' ? $row->stage_execution_summary : null,
                is_string($row->handoff_summary ?? null) && $row->handoff_summary !== '' ? $row->handoff_summary : null,
            ], static fn (?string $p): bool => $p !== null);
            $summary = $parts !== [] ? implode("\n\n", $parts) : '—';
            if (function_exists('mb_strlen') && mb_strlen($summary) > 65000) {
                $summary = mb_substr($summary, 0, 65000);
            } elseif (strlen($summary) > 65000) {
                $summary = substr($summary, 0, 65000);
            }

            DB::table('task_stage_histories')->insert([
                'task_id' => $row->id,
                'stage' => $row->current_stage,
                'summary' => $summary,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }
    }

    private function dropLegacyColumns(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn([
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
        } finally {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }
};
