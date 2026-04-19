@php
    $selectedProjectId = (int) old('project_id', $task->project_id ?? 0);
    $selectedEnvironmentProfileId = old('environment_profile_id', $task->environment_profile_id ?? '');
    $selectedStatus = old('status', $task->status->value ?? \App\Support\Enums\TaskStatus::Pending->value);
    $selectedPriority = old('priority', $task->priority->value ?? \App\Support\Enums\TaskPriority::Medium->value);
    $selectedImplementationType = old('implementation_type', $task->implementation_type?->value ?? \App\Support\Enums\TaskImplementationType::Feature->value);
    $selectedCurrentStage = old('current_stage', $task->current_stage?->value ?? \App\Support\Enums\TaskStage::Analysis->value);
    $selectedAnalysisDomain = old('analysis_domain', $task->analysis_domain?->value ?? '');
    $selectedAnalysisNextStage = old('analysis_next_stage', $task->analysis_next_stage?->value ?? '');
    $selectedExecutionStage = old('stage_execution_stage', $task->stage_execution_stage?->value ?? '');
    $selectedHandoffFromStage = old('handoff_from_stage', $task->handoff_from_stage?->value ?? '');
    $selectedHandoffToStage = old('handoff_to_stage', $task->handoff_to_stage?->value ?? '');
    $stageExecutionStartedAt = old('stage_execution_started_at', $task->stage_execution_started_at?->format('Y-m-d\TH:i') ?? '');
    $stageExecutionFinishedAt = old('stage_execution_finished_at', $task->stage_execution_finished_at?->format('Y-m-d\TH:i') ?? '');
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $jsonFieldValue = static function (string $field, mixed $value) use ($jsonFlags): string {
        $oldValue = old($field);

        if (is_string($oldValue)) {
            return $oldValue;
        }

        if (is_array($oldValue)) {
            return json_encode($oldValue, $jsonFlags) ?: '';
        }

        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        return json_encode($value, $jsonFlags) ?: '';
    };
    $environmentProfileOptions = $environmentProfiles
        ->map(fn ($profile) => [
            'id' => $profile->id,
            'project_id' => $profile->project_id,
            'project_name' => $profile->project?->name ?? '',
            'name' => $profile->name,
            'slug' => $profile->slug,
        ])
        ->values();
@endphp

<div
    x-data="{
        selectedProjectId: @js($selectedProjectId > 0 ? (string) $selectedProjectId : ''),
        selectedEnvironmentProfileId: @js((string) $selectedEnvironmentProfileId),
        environmentProfiles: @js($environmentProfileOptions),
        get filteredEnvironmentProfiles() {
            if (this.selectedProjectId === '') {
                return [];
            }

            return this.environmentProfiles.filter((profile) => String(profile.project_id) === String(this.selectedProjectId));
        },
        syncEnvironmentProfileSelection() {
            if (this.selectedEnvironmentProfileId === '') {
                return;
            }

            const hasSelectedProfile = this.filteredEnvironmentProfiles.some((profile) => String(profile.id) === String(this.selectedEnvironmentProfileId));

            if (! hasSelectedProfile) {
                this.selectedEnvironmentProfileId = '';
            }
        },
    }"
    x-init="syncEnvironmentProfileSelection()"
    x-effect="syncEnvironmentProfileSelection()"
    class="space-y-8"
>
    <section class="rounded-3xl border border-slate-200 bg-slate-50/70 p-6">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-slate-950">Dados gerais da task</h2>
            <p class="mt-1 text-sm text-slate-500">Informações principais da tarefa e seu contexto operacional atual.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="project_id" value="Projeto" />
                <select id="project_id" name="project_id" x-model="selectedProjectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">Selecione</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('project_id')" />
            </div>

            <div>
                <x-input-label for="environment_profile_id" value="Perfil de ambiente (opcional)" />
                <select id="environment_profile_id" name="environment_profile_id" x-model="selectedEnvironmentProfileId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Sem</option>
                    <template x-for="profile in filteredEnvironmentProfiles" :key="profile.id">
                        <option :value="profile.id" x-text="`${profile.project_name} — ${profile.name} (${profile.slug})`"></option>
                    </template>
                </select>
                <p x-cloak x-show="selectedProjectId !== '' && filteredEnvironmentProfiles.length === 0" class="mt-2 text-sm text-slate-500">
                    O projeto selecionado não possui perfis de ambiente cadastrados.
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('environment_profile_id')" />
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="title" value="Título" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $task->title ?? '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('title')" />
        </div>

        <div class="mt-6">
            <x-input-label for="description" value="Descrição" />
            <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('description', $task->description ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($statusPresentations as $statusValue => $statusPresentation)
                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>
                            {{ $statusPresentation['label'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status')" />
            </div>

            <div>
                <x-input-label for="priority" value="Prioridade" />
                <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach (\App\Support\Enums\TaskPriority::cases() as $case)
                        <option value="{{ $case->value }}" @selected($selectedPriority === $case->value)>
                            {{ $case->value }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('priority')" />
            </div>

            <div>
                <x-input-label for="implementation_type" value="Tipo de implementação" />
                <select id="implementation_type" name="implementation_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach (\App\Support\Enums\TaskImplementationType::cases() as $case)
                        <option value="{{ $case->value }}" @selected($selectedImplementationType === $case->value)>
                            {{ $case->value }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('implementation_type')" />
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="deliverables" value="Entregáveis (opcional)" />
                <textarea id="deliverables" name="deliverables" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('deliverables', $task->deliverables ?? '') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('deliverables')" />
            </div>

            <div>
                <x-input-label for="constraints" value="Restrições (opcional)" />
                <textarea id="constraints" name="constraints" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('constraints', $task->constraints ?? '') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('constraints')" />
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-sky-200 bg-sky-50/70 p-6">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-slate-950">Estágio atual</h2>
            <p class="mt-1 text-sm text-slate-500">Define em qual etapa do fluxo orientado por estágio a task se encontra.</p>
        </div>

        <div class="max-w-xl">
            <x-input-label for="current_stage" value="Estágio atual" />
            <select id="current_stage" name="current_stage" class="mt-1 block w-full rounded-md border-sky-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                @foreach ($stagePresentations as $stageValue => $stagePresentation)
                    <option value="{{ $stageValue }}" @selected($selectedCurrentStage === $stageValue)>
                        {{ $stagePresentation['label'] }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('current_stage')" />
        </div>
    </section>

    <section class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-6">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-slate-950">Resultado da análise</h2>
            <p class="mt-1 text-sm text-slate-500">Dados produzidos pela etapa de análise e referência para a próxima etapa sugerida.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div>
                <x-input-label for="analysis_domain" value="Domínio identificado" />
                <select id="analysis_domain" name="analysis_domain" class="mt-1 block w-full rounded-md border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Não definido</option>
                    @foreach (\App\Support\Enums\TaskAnalysisDomain::cases() as $domain)
                        <option value="{{ $domain->value }}" @selected($selectedAnalysisDomain === $domain->value)>
                            {{ $domain->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('analysis_domain')" />
            </div>

            <div>
                <x-input-label for="analysis_confidence" value="Confiança (0.00 a 1.00)" />
                <x-text-input id="analysis_confidence" name="analysis_confidence" type="number" min="0" max="1" step="0.01" class="mt-1 block w-full" :value="old('analysis_confidence', $task->analysis_confidence ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('analysis_confidence')" />
            </div>

            <div>
                <x-input-label for="analysis_next_stage" value="Próximo estágio sugerido" />
                <select id="analysis_next_stage" name="analysis_next_stage" class="mt-1 block w-full rounded-md border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Não definido</option>
                    @foreach ($stagePresentations as $stageValue => $stagePresentation)
                        <option value="{{ $stageValue }}" @selected($selectedAnalysisNextStage === $stageValue)>
                            {{ $stagePresentation['label'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('analysis_next_stage')" />
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="analysis_summary" value="Resumo da análise" />
            <textarea id="analysis_summary" name="analysis_summary" rows="4" class="mt-1 block w-full rounded-md border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('analysis_summary', $task->analysis_summary ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('analysis_summary')" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div>
                <x-input-label for="analysis_evidence" value="Evidências (JSON)" />
                <textarea id="analysis_evidence" name="analysis_evidence" rows="8" class="mt-1 block w-full rounded-md border-emerald-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $jsonFieldValue('analysis_evidence', $task->analysis_evidence ?? null) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('analysis_evidence')" />
            </div>

            <div>
                <x-input-label for="analysis_risks" value="Riscos (JSON)" />
                <textarea id="analysis_risks" name="analysis_risks" rows="8" class="mt-1 block w-full rounded-md border-emerald-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $jsonFieldValue('analysis_risks', $task->analysis_risks ?? null) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('analysis_risks')" />
            </div>

            <div>
                <x-input-label for="analysis_artifacts" value="Artefatos (JSON)" />
                <textarea id="analysis_artifacts" name="analysis_artifacts" rows="8" class="mt-1 block w-full rounded-md border-emerald-300 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $jsonFieldValue('analysis_artifacts', $task->analysis_artifacts ?? null) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('analysis_artifacts')" />
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="analysis_notes" value="Observações para o próximo estágio" />
            <textarea id="analysis_notes" name="analysis_notes" rows="4" class="mt-1 block w-full rounded-md border-emerald-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('analysis_notes', $task->analysis_notes ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('analysis_notes')" />
        </div>
    </section>

    <section class="rounded-3xl border border-amber-200 bg-amber-50/70 p-6">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-slate-950">Dados da execução da etapa</h2>
            <p class="mt-1 text-sm text-slate-500">Snapshot manual da execução atual ou da última execução relevante para o fluxo por estágio.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <x-input-label for="stage_execution_reference" value="Identificador da execução" />
                <x-text-input id="stage_execution_reference" name="stage_execution_reference" type="text" class="mt-1 block w-full" :value="old('stage_execution_reference', $task->stage_execution_reference ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_reference')" />
            </div>

            <div>
                <x-input-label for="stage_execution_stage" value="Estágio da execução" />
                <select id="stage_execution_stage" name="stage_execution_stage" class="mt-1 block w-full rounded-md border-amber-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Não definido</option>
                    @foreach ($stagePresentations as $stageValue => $stagePresentation)
                        <option value="{{ $stageValue }}" @selected($selectedExecutionStage === $stageValue)>
                            {{ $stagePresentation['label'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_stage')" />
            </div>

            <div>
                <x-input-label for="stage_execution_status" value="Status da execução" />
                <x-text-input id="stage_execution_status" name="stage_execution_status" type="text" class="mt-1 block w-full" :value="old('stage_execution_status', $task->stage_execution_status ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_status')" />
            </div>

            <div>
                <x-input-label for="stage_execution_agent" value="Agente responsável" />
                <x-text-input id="stage_execution_agent" name="stage_execution_agent" type="text" class="mt-1 block w-full" :value="old('stage_execution_agent', $task->stage_execution_agent ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_agent')" />
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div>
                <x-input-label for="stage_execution_exit_code" value="Exit code" />
                <x-text-input id="stage_execution_exit_code" name="stage_execution_exit_code" type="number" class="mt-1 block w-full" :value="old('stage_execution_exit_code', $task->stage_execution_exit_code ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_exit_code')" />
            </div>

            <div>
                <x-input-label for="stage_execution_started_at" value="Iniciado em" />
                <x-text-input id="stage_execution_started_at" name="stage_execution_started_at" type="datetime-local" class="mt-1 block w-full" :value="$stageExecutionStartedAt" />
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_started_at')" />
            </div>

            <div>
                <x-input-label for="stage_execution_finished_at" value="Finalizado em" />
                <x-text-input id="stage_execution_finished_at" name="stage_execution_finished_at" type="datetime-local" class="mt-1 block w-full" :value="$stageExecutionFinishedAt" />
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_finished_at')" />
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="stage_execution_summary" value="Resumo da execução" />
            <textarea id="stage_execution_summary" name="stage_execution_summary" rows="4" class="mt-1 block w-full rounded-md border-amber-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ old('stage_execution_summary', $task->stage_execution_summary ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('stage_execution_summary')" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div>
                <x-input-label for="stage_execution_output" value="Saída estruturada (JSON)" />
                <textarea id="stage_execution_output" name="stage_execution_output" rows="8" class="mt-1 block w-full rounded-md border-amber-300 font-mono text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ $jsonFieldValue('stage_execution_output', $task->stage_execution_output ?? null) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_output')" />
            </div>

            <div>
                <x-input-label for="stage_execution_context" value="Contexto / ambiente (JSON)" />
                <textarea id="stage_execution_context" name="stage_execution_context" rows="8" class="mt-1 block w-full rounded-md border-amber-300 font-mono text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ $jsonFieldValue('stage_execution_context', $task->stage_execution_context ?? null) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('stage_execution_context')" />
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="stage_execution_raw_output" value="Saída bruta" />
            <textarea id="stage_execution_raw_output" name="stage_execution_raw_output" rows="8" class="mt-1 block w-full rounded-md border-amber-300 font-mono text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ old('stage_execution_raw_output', $task->stage_execution_raw_output ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('stage_execution_raw_output')" />
        </div>

        @isset($task)
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-700">Metadados atuais do worker</h3>
                <p class="mt-1 text-xs text-slate-500">Bloco somente leitura com o runtime já usado pelo worker atual.</p>

                <dl class="mt-4 grid grid-cols-1 gap-4 text-sm md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Worker atual</dt>
                        <dd class="mt-1 text-gray-800">{{ $task->claimed_by_worker ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Claim em</dt>
                        <dd class="mt-1 text-gray-800">{{ $task->claimed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Último heartbeat</dt>
                        <dd class="mt-1 text-gray-800">{{ $task->last_heartbeat_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Lock até</dt>
                        <dd class="mt-1 text-gray-800">{{ $task->locked_until?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Tentativas</dt>
                        <dd class="mt-1 text-gray-800">{{ $task->attempts }} / {{ $task->max_attempts }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Executar após</dt>
                        <dd class="mt-1 text-gray-800">{{ $task->run_after?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        @endisset
    </section>

    <section class="rounded-3xl border border-violet-200 bg-violet-50/70 p-6">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-slate-950">Handoff entre etapas</h2>
            <p class="mt-1 text-sm text-slate-500">Informações de repasse entre estágios para orientar a próxima execução humana ou automática.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
            <div>
                <x-input-label for="handoff_from_stage" value="Estágio de origem" />
                <select id="handoff_from_stage" name="handoff_from_stage" class="mt-1 block w-full rounded-md border-violet-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Não definido</option>
                    @foreach ($stagePresentations as $stageValue => $stagePresentation)
                        <option value="{{ $stageValue }}" @selected($selectedHandoffFromStage === $stageValue)>
                            {{ $stagePresentation['label'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('handoff_from_stage')" />
            </div>

            <div>
                <x-input-label for="handoff_to_stage" value="Estágio de destino" />
                <select id="handoff_to_stage" name="handoff_to_stage" class="mt-1 block w-full rounded-md border-violet-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Não definido</option>
                    @foreach ($stagePresentations as $stageValue => $stagePresentation)
                        <option value="{{ $stageValue }}" @selected($selectedHandoffToStage === $stageValue)>
                            {{ $stagePresentation['label'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('handoff_to_stage')" />
            </div>

            <div>
                <x-input-label for="handoff_confidence" value="Confiança (0.00 a 1.00)" />
                <x-text-input id="handoff_confidence" name="handoff_confidence" type="number" min="0" max="1" step="0.01" class="mt-1 block w-full" :value="old('handoff_confidence', $task->handoff_confidence ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('handoff_confidence')" />
            </div>

            <div>
                <x-input-label for="handoff_reason" value="Motivo do handoff" />
                <x-text-input id="handoff_reason" name="handoff_reason" type="text" class="mt-1 block w-full" :value="old('handoff_reason', $task->handoff_reason ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('handoff_reason')" />
            </div>
        </div>

        <div class="mt-6">
            <x-input-label for="handoff_summary" value="Resumo do handoff" />
            <textarea id="handoff_summary" name="handoff_summary" rows="4" class="mt-1 block w-full rounded-md border-violet-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">{{ old('handoff_summary', $task->handoff_summary ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('handoff_summary')" />
        </div>

        <div class="mt-6">
            <x-input-label for="handoff_payload" value="Payload repassado (JSON)" />
            <textarea id="handoff_payload" name="handoff_payload" rows="8" class="mt-1 block w-full rounded-md border-violet-300 font-mono text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">{{ $jsonFieldValue('handoff_payload', $task->handoff_payload ?? null) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('handoff_payload')" />
        </div>
    </section>
</div>
