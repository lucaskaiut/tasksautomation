@extends('layouts.app')

@section('title', $task->title)
@section('page-title', $task->title)
@section('page-description')
    {{ $task->project?->name }} · {{ $task->environmentProfile?->name ?? 'Perfil padrão' }}
@endsection

@section('page-actions')
    <a href="{{ route('tasks.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">
        Voltar à lista
    </a>
    <a href="{{ route('tasks.edit', $task) }}" class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
        Editar
    </a>
@endsection

@section('content')
    @php($statusPresentation = $statusPresentations[$task->status->value] ?? ['label' => $task->status->value, 'badge_classes' => 'bg-slate-100 text-slate-700'])
    @php($stagePresentation = $stagePresentations[$task->current_stage->value] ?? ['label' => $task->current_stage->value, 'badge_classes' => 'bg-slate-100 text-slate-700'])
    @php($reviewStatusPresentation = $task->review_status ? ($reviewStatusPresentations[$task->review_status->value] ?? ['label' => $task->review_status->value, 'badge_classes' => 'bg-slate-100 text-slate-700']) : null)
    <script type="application/json" id="task-stream-config">@json($realtimeConfig)</script>

    <div class="space-y-8" data-task-show data-task-id="{{ $task->id }}">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Resumo</h3>
                    <p class="mt-2 max-w-3xl whitespace-pre-wrap text-sm text-slate-700" data-task-field="description">{{ $task->description }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $stagePresentation['badge_classes'] }}">
                        {{ $stagePresentation['label'] }}
                    </span>
                    <span
                        data-task-field="status-badge"
                        data-base-class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusPresentation['badge_classes'] }}"
                    >
                        <span data-task-field="status-label">{{ $statusPresentation['label'] }}</span>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        {{ $task->implementation_type?->value }}
                    </span>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Estágio atual</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-950">{{ $stagePresentation['label'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Revisão funcional</dt>
                    <dd class="mt-1">
                        <span
                            data-task-field="review-status-badge"
                            data-base-class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $reviewStatusPresentation['badge_classes'] ?? 'bg-slate-100 text-slate-700' }}"
                        >
                            <span data-task-field="review-status-label">{{ $reviewStatusPresentation['label'] ?? '—' }}</span>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Prioridade</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-950" data-task-field="priority">{{ $task->priority->value }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Worker atual</dt>
                    <dd class="mt-1 text-sm text-slate-950" data-task-field="worker">{{ $task->claimed_by_worker ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Revisões com ajuste</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-950" data-task-field="revision-count">{{ $task->revision_count }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Última revisão</dt>
                    <dd class="mt-1 text-sm text-slate-950" data-task-field="last-review">
                        @if ($task->last_reviewed_at)
                            {{ $task->last_reviewed_at->format('d/m/Y H:i') }}
                            @if ($task->lastReviewer)
                                <span class="text-slate-500">· {{ $task->lastReviewer->name }}</span>
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Criada por</dt>
                    <dd class="mt-1 text-sm text-slate-950">{{ $task->creator?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Entregáveis</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-950" data-task-field="deliverables">{{ $task->deliverables ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Restrições</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-950" data-task-field="constraints">{{ $task->constraints ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl border border-sky-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Evolução de estágios</h3>
            <p class="mt-1 text-sm text-slate-500">Histórico de transições com resumo. Cada alteração adiciona uma linha.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">#</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Estágio</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Resumo</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Quando</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($task->stageHistories as $entry)
                            @php($entryStage = $stagePresentations[$entry->stage->value] ?? ['label' => $entry->stage->value, 'badge_classes' => 'bg-slate-100 text-slate-700'])
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $entry->id }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $entryStage['badge_classes'] }}">
                                        {{ $entryStage['label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 max-w-prose whitespace-pre-wrap text-slate-800">{{ $entry->summary }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-slate-500">Sem registo ainda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h4 class="text-sm font-semibold text-slate-900">Registar transição de estágio</h4>
                <form method="post" action="{{ route('tasks.change-stage', $task) }}" class="mt-4 space-y-4 max-w-2xl">
                    @csrf
                    <div>
                        <label for="change_stage" class="block text-sm font-medium text-slate-700">Novo estágio</label>
                        <select id="change_stage" name="stage" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                            @foreach ($stagePresentations as $stageValue => $s)
                                <option value="{{ $stageValue }}" @selected(old('stage', $task->current_stage->value) === $stageValue)>
                                    {{ $s['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('stage')" class="mt-2" />
                    </div>
                    <div>
                        <label for="change_stage_summary" class="block text-sm font-medium text-slate-700">Resumo</label>
                        <textarea id="change_stage_summary" name="summary" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required placeholder="Descreva o contexto desta transição.">{{ old('summary') }}</textarea>
                        <x-input-error :messages="$errors->get('summary')" class="mt-2" />
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center rounded-2xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500">
                            Atualizar estágio
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($reviewableExecution)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Registrar revisão funcional</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Execução #{{ $reviewableExecution->id }} aguarda aprovação ou pedido de ajustes.
                </p>

                @if ($errors->any())
                    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('tasks.executions.reviews.store', [$task, $reviewableExecution]) }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="decision" class="block text-sm font-medium text-slate-700">Decisão</label>
                        <select id="decision" name="decision" class="mt-1 block w-full max-w-md rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                            <option value="">Selecione…</option>
                            <option value="approved" @selected(old('decision') === 'approved')>Aprovar</option>
                            <option value="needs_adjustment" @selected(old('decision') === 'needs_adjustment')>Solicitar ajustes</option>
                        </select>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700">Notas</label>
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('notes') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Obrigatório ao solicitar ajustes.</p>
                    </div>
                    <div>
                        <label for="current_behavior" class="block text-sm font-medium text-slate-700">Comportamento atual</label>
                        <textarea id="current_behavior" name="current_behavior" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('current_behavior') }}</textarea>
                    </div>
                    <div>
                        <label for="expected_behavior" class="block text-sm font-medium text-slate-700">Comportamento esperado</label>
                        <textarea id="expected_behavior" name="expected_behavior" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('expected_behavior') }}</textarea>
                    </div>
                    <div>
                        <label for="preserve_scope" class="block text-sm font-medium text-slate-700">O que preservar</label>
                        <textarea id="preserve_scope" name="preserve_scope" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('preserve_scope') }}</textarea>
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center rounded-2xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500">
                            Enviar revisão
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Histórico de execuções</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">#</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Worker</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Início / fim</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Resumo</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">PR</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Falha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($task->executions as $execution)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-800">{{ $execution->id }}</td>
                                <td class="px-3 py-2 text-slate-800">{{ $execution->status->value }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $execution->worker_id ?? '—' }}</td>
                                <td class="px-3 py-2 text-slate-600">
                                    <div class="text-xs">{{ optional($execution->started_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                    <div class="text-xs text-slate-400">{{ optional($execution->finished_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ \Illuminate\Support\Str::limit($execution->summary ?? '—', 120) }}</td>
                                <td class="px-3 py-2">
                                    @if ($execution->pull_request_url)
                                        <a href="{{ $execution->pull_request_url }}" class="font-semibold text-sky-700 hover:underline" target="_blank" rel="noopener noreferrer">link</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-rose-700">{{ \Illuminate\Support\Str::limit($execution->failure_reason ?? '—', 80) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-slate-500">Nenhuma execução registrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Histórico de revisões</h3>
            <div class="mt-4 space-y-4">
                @forelse ($task->reviews as $review)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span class="font-semibold text-slate-950">{{ $review->decision->value }}</span>
                            <span class="text-slate-500">{{ $review->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">
                            Por {{ $review->author?->name ?? '—' }} · execução #{{ $review->task_execution_id }}
                        </p>
                        <p class="mt-3 whitespace-pre-wrap text-sm text-slate-800">{{ $review->notes }}</p>
                        @if ($review->current_behavior || $review->expected_behavior || $review->preserve_scope)
                            <dl class="mt-3 grid gap-2 text-xs text-slate-700">
                                @if ($review->current_behavior)
                                    <div><span class="font-semibold">Atual:</span> {{ $review->current_behavior }}</div>
                                @endif
                                @if ($review->expected_behavior)
                                    <div><span class="font-semibold">Esperado:</span> {{ $review->expected_behavior }}</div>
                                @endif
                                @if ($review->preserve_scope)
                                    <div><span class="font-semibold">Preservar:</span> {{ $review->preserve_scope }}</div>
                                @endif
                            </dl>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhuma revisão registrada.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
