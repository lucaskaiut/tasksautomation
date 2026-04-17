@extends('layouts.app')

@section('title', 'Projetos')
@section('page-title', 'Projetos')
@section('page-description', 'Gerencie os projetos cadastrados e acesse rapidamente seus perfis de ambiente.')

@section('page-actions')
    <a
        href="{{ route('projects.create') }}"
        class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
    >
        Novo projeto
    </a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Slug</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ativo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Repositório</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($projects as $project)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-950">{{ $project->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $project->slug }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span class="{{ $project->is_active ? 'text-emerald-700' : 'text-slate-500' }}">
                                    {{ $project->is_active ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <a href="{{ $project->repository_url }}" class="text-sky-700 hover:underline" target="_blank" rel="noreferrer">
                                    {{ $project->repository_url }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('projects.environment-profiles.index', $project) }}" class="mr-3 font-semibold text-slate-700 hover:underline">
                                    Perfis
                                </a>
                                <a href="{{ route('projects.edit', $project) }}" class="font-semibold text-sky-700 hover:underline">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                Nenhum projeto cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@endsection
