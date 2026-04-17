@extends('layouts.app')

@section('title', 'Minha conta')
@section('page-title', 'Minha conta')
@section('page-description', 'Atualize seus dados de acesso, senha e preferências da conta.')

@section('content')
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
