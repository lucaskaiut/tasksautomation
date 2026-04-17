@extends('layouts.app')

@section('title', 'Editar tarefa')
@section('page-title', 'Editar tarefa')
@section('page-description', 'Atualize os dados da tarefa sem perder contexto de execução e revisão.')

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('tasks.partials.form', ['task' => $task])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('tasks.index') }}" class="text-sm font-medium text-slate-600 hover:underline">
                    Voltar
                </a>
                <x-primary-button>
                    Salvar
                </x-primary-button>
            </div>
        </form>
    </div>
@endsection
