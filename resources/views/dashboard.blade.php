@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Resumo rápido da sua área autenticada.')

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-700">{{ __("You're logged in!") }}</p>
    </div>
@endsection
