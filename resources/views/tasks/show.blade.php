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
    @php($reviewStatusPresentation = $task->review_status ? ($reviewStatusPresentations[$task->review_status->value] ?? ['label' => $task->review_status->value, 'badge_classes' => 'bg-slate-100 text-slate-700']) : null)
    <div class="space-y-8">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Resumo</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusPresentation['badge_classes'] }}">
                            {{ $statusPresentation['label'] }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Revisão funcional</dt>
                    <dd class="mt-1">
                        @if ($reviewStatusPresentation)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $reviewStatusPresentation['badge_classes'] }}">
                                {{ $reviewStatusPresentation['label'] }}
                            </span>
                        @else
                            <span class="text-sm font-medium text-slate-950">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Revisões com ajuste</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-950">{{ $task->revision_count }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Prioridade</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-950">{{ $task->priority->value }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase text-slate-500">Última revisão</dt>
                    <dd class="mt-1 text-sm text-slate-950">
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
                    <dt class="text-xs font-medium uppercase text-slate-500">Worker atual</dt>
                    <dd class="mt-1 text-sm text-slate-950">{{ $task->claimed_by_worker ?? '—' }}</dd>
                </div>
            </dl>
            <div class="mt-6 space-y-3 text-sm text-slate-800">
                <div>
                    <h4 class="font-semibold text-slate-950">Descrição</h4>
                    <p class="mt-1 whitespace-pre-wrap">{{ $task->description }}</p>
                </div>
                @if ($task->deliverables)
                    <div>
                        <h4 class="font-semibold text-slate-950">Entregáveis</h4>
                        <p class="mt-1 whitespace-pre-wrap">{{ $task->deliverables }}</p>
                    </div>
                @endif
                @if ($task->constraints)
                    <div>
                        <h4 class="font-semibold text-slate-950">Restrições</h4>
                        <p class="mt-1 whitespace-pre-wrap">{{ $task->constraints }}</p>
                    </div>
                @endif
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
