@php
    $task = $task ?? null;
    $selectedProjectId = (int) old('project_id', $task?->project_id ?? 0);
    $selectedEnvironmentProfileId = old('environment_profile_id', $task?->environment_profile_id ?? '');
    $selectedStatus = old('status', $task?->status->value ?? \App\Support\Enums\TaskStatus::Pending->value);
    $selectedPriority = old('priority', $task?->priority->value ?? \App\Support\Enums\TaskPriority::Medium->value);
    $selectedImplementationType = old('implementation_type', $task?->implementation_type?->value ?? \App\Support\Enums\TaskImplementationType::Feature->value);
    $selectedCurrentStage = old('current_stage', $task?->current_stage?->value ?? \App\Support\Enums\TaskStage::Analysis->value);
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
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $task?->title ?? '')" required />
            <x-input-error class="mt-2" :messages="$errors->get('title')" />
        </div>

        <div class="mt-6">
            <x-input-label for="description" value="Descrição" />
            <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('description', $task?->description ?? '') }}</textarea>
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
                <textarea id="deliverables" name="deliverables" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('deliverables', $task?->deliverables ?? '') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('deliverables')" />
            </div>

            <div>
                <x-input-label for="constraints" value="Restrições (opcional)" />
                <textarea id="constraints" name="constraints" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('constraints', $task?->constraints ?? '') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('constraints')" />
            </div>
        </div>
    </section>

    @if ($task === null)
        <section class="rounded-3xl border border-sky-200 bg-sky-50/70 p-6">
            <div class="mb-6">
                <h2 class="text-base font-semibold text-slate-950">Estágio inicial</h2>
                <p class="mt-1 text-sm text-slate-500">Registre o primeiro estágio; alterações futuras usam a ficha da tarefa.</p>
            </div>

            <div class="max-w-xl">
                <x-input-label for="current_stage" value="Estágio" />
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
    @endif

    @if ($task)
        <section class="rounded-3xl border border-slate-200 bg-slate-50/70 p-6">
            <h2 class="text-base font-semibold text-slate-950">Metadados do worker</h2>
            <p class="mt-1 text-sm text-slate-500">Somente leitura. Para mudar o estágio, use a ficha da tarefa.</p>
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
        </section>
    @endif
</div>
