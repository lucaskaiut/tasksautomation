@extends('layouts.app')

@section('title', 'Editar perfil de ambiente')
@section('page-title', 'Editar perfil de ambiente')
@section('page-description')
    Projeto: <span class="font-medium text-slate-700">{{ $project->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('projects.environment-profiles.index', $project) }}" class="text-sm font-medium text-slate-600 hover:underline">
        Voltar
    </a>
@endsection

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('projects.environment-profiles.update', [$project, $profile]) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('projects.environment-profiles.partials.form', ['profile' => $profile])

            <div class="flex justify-end">
                <x-primary-button>Salvar</x-primary-button>
            </div>
        </form>
    </div>
@endsection
