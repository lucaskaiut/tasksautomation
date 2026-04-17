@extends('layouts.app')

@section('title', 'Editar projeto')
@section('page-title', 'Editar projeto')
@section('page-description', 'Atualize os dados gerais do projeto e mantenha as integrações consistentes.')

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('projects.partials.form', ['project' => $project])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('projects.index') }}" class="text-sm font-medium text-slate-600 hover:underline">
                    Voltar
                </a>
                <x-primary-button>
                    Salvar
                </x-primary-button>
            </div>
        </form>
    </div>
@endsection
