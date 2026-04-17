<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
        @php
            $pageTitle = trim($__env->yieldContent('page-title', $__env->yieldContent('title', config('app.name', 'Laravel'))));
            $pageDescription = trim($__env->yieldContent('page-description'));
            $pageActions = trim($__env->yieldContent('page-actions'));
            $slotContent = isset($slot) ? trim((string) $slot) : '';
            $headerContent = isset($header) ? trim((string) $header) : '';
        @endphp

        <div class="min-h-screen lg:flex">
            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden"
                @click="sidebarOpen = false"
            ></div>

            @include('layouts.partials.sidebar')

            <div class="flex min-h-screen flex-1 flex-col lg:pl-72">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div class="flex items-center gap-4 px-4 py-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:text-slate-900 lg:hidden"
                            @click="sidebarOpen = true"
                        >
                            <span class="sr-only">Abrir menu</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            @if ($headerContent !== '')
                                {{ $header }}
                            @else
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Painel administrativo</p>
                                        <h1 class="truncate text-2xl font-semibold text-slate-950">
                                            {{ $pageTitle }}
                                        </h1>

                                        @if ($pageDescription !== '')
                                            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                                                @yield('page-description')
                                            </p>
                                        @endif
                                    </div>

                                    @if ($pageActions !== '')
                                        <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                            @yield('page-actions')
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @include('layouts.partials.flash-messages')

                    <div class="@yield('content-classes', 'space-y-6')">
                        @yield('content')

                        @if ($slotContent !== '')
                            {{ $slot }}
                        @endif
                    </div>
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
