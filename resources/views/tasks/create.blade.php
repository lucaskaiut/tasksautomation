@extends('layouts.app')

@section('title', 'Nova tarefa')
@section('page-title', 'Nova tarefa')
@section('page-description', 'Registre a tarefa com estágio inicial; as mudanças de estágio ficam no histórico.')

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('tasks.store') }}" class="space-y-6">
            @csrf

            @include('tasks.partials.form')

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('tasks.index') }}" class="text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
                <x-primary-button>
                    Salvar
                </x-primary-button>
            </div>
        </form>
    </div>
@endsection
