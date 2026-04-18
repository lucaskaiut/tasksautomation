@extends('layouts.app')

@section('title', 'Tarefas')
@section('page-title', 'Tarefas')
@section('page-description', 'Acompanhe o fluxo de execução, revisão e prioridade das tarefas em andamento.')

@section('page-actions')
    <a
        href="{{ route('tasks.create') }}"
        class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
    >
        Nova tarefa
    </a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Título</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Projeto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Implementação</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Revisão</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ajustes</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Última revisão</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Prioridade</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Worker</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tentativas</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Criador</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($tasks as $task)
                        @php($statusPresentation = $statusPresentations[$task->status->value] ?? ['label' => $task->status->value, 'badge_classes' => 'bg-slate-100 text-slate-700'])
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-950">{{ $task->title }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $task->project?->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusPresentation['badge_classes'] }}">
                                    {{ $statusPresentation['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                    {{ $task->implementation_type?->value }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $task->review_status?->value ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $task->revision_count }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if ($task->last_reviewed_at)
                                    <span class="text-xs">{{ $task->last_reviewed_at->format('d/m/Y H:i') }}</span>
                                    @if ($task->lastReviewer)
                                        <span class="block text-xs text-slate-400">{{ $task->lastReviewer->name }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $task->priority->value }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if ($task->claimed_by_worker)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $task->claimed_by_worker }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span class="font-mono text-xs text-slate-800">
                                    {{ $task->attempts }} / {{ $task->max_attempts }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $task->creator?->name }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('tasks.show', $task) }}" class="font-semibold text-sky-700 hover:underline">
                                    Ver
                                </a>
                                <span class="text-slate-300">·</span>
                                <a href="{{ route('tasks.edit', $task) }}" class="font-semibold text-sky-700 hover:underline">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-10 text-center text-sm text-slate-500">
                                Nenhuma tarefa cadastrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tasks->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
@endsection
