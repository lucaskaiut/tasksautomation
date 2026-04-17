@extends('layouts.app')

@section('title', 'Novo projeto')
@section('page-title', 'Novo projeto')
@section('page-description', 'Cadastre um novo projeto com repositório, branch padrão e regras globais.')

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-6">
            @csrf

            @include('projects.partials.form')

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('projects.index') }}" class="text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
                <x-primary-button>
                    Salvar
                </x-primary-button>
            </div>
        </form>
    </div>
@endsection
