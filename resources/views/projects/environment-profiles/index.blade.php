@extends('layouts.app')

@section('title', 'Perfis de ambiente')
@section('page-title', 'Perfis de ambiente')
@section('page-description')
    Projeto: <span class="font-medium text-slate-700">{{ $project->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('projects.index') }}" class="text-sm font-medium text-slate-600 hover:underline">
        Voltar
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-950">Perfis cadastrados</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Padrão</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($profiles as $profile)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-slate-950">{{ $profile->name }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600">{{ $profile->slug }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600">
                                    @if ($profile->is_default)
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">Sim</span>
                                    @else
                                        <span class="text-slate-500">Não</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-sm">
                                    <a href="{{ route('projects.environment-profiles.edit', [$project, $profile]) }}" class="font-semibold text-sky-700 hover:underline">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Nenhum perfil cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-950">Novo perfil</h3>

            <form method="POST" action="{{ route('projects.environment-profiles.store', $project) }}" class="mt-4 space-y-6">
                @csrf

                @include('projects.environment-profiles.partials.form', ['profile' => null])

                <div class="flex justify-end">
                    <x-primary-button>Salvar</x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection
