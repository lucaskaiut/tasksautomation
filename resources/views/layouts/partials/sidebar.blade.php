@php
    $navigationItems = [
        [
            'label' => 'Projetos',
            'route' => route('projects.index'),
            'active' => request()->routeIs('projects.*'),
            'icon' => 'M3 7.75A2.75 2.75 0 0 1 5.75 5h12.5A2.75 2.75 0 0 1 21 7.75v8.5A2.75 2.75 0 0 1 18.25 19H5.75A2.75 2.75 0 0 1 3 16.25zm4.5 1.75a.75.75 0 0 0 0 1.5h9a.75.75 0 0 0 0-1.5zm0 4a.75.75 0 0 0 0 1.5h5a.75.75 0 0 0 0-1.5z',
        ],
        [
            'label' => 'Tarefas',
            'route' => route('tasks.index'),
            'active' => request()->routeIs('tasks.*'),
            'icon' => 'M5.75 4A2.75 2.75 0 0 0 3 6.75v10.5A2.75 2.75 0 0 0 5.75 20h12.5A2.75 2.75 0 0 0 21 17.25V6.75A2.75 2.75 0 0 0 18.25 4zm1.5 4.25a.75.75 0 0 0 0 1.5h9.5a.75.75 0 0 0 0-1.5zm0 4a.75.75 0 0 0 0 1.5h9.5a.75.75 0 0 0 0-1.5zm0 4a.75.75 0 0 0 0 1.5h5.5a.75.75 0 0 0 0-1.5z',
        ],
        [
            'label' => 'Minha conta',
            'route' => route('profile.edit'),
            'active' => request()->routeIs('profile.*'),
            'icon' => 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4m0 2c-3.28 0-6.5 1.67-6.5 4.25A.75.75 0 0 0 6.25 19h11.5a.75.75 0 0 0 .75-.75C18.5 15.67 15.28 14 12 14',
        ],
    ];
@endphp

<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-slate-950 text-slate-100 transition-transform duration-200 ease-out lg:translate-x-0"
>
    <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
        <a href="{{ route('projects.index') }}" class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-white">
                <x-application-logo class="h-7 w-7 fill-current" />
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Workspace</p>
                <p class="text-sm font-semibold text-white">{{ config('app.name', 'Laravel') }}</p>
            </div>
        </a>

        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
            @click="sidebarOpen = false"
        >
            <span class="sr-only">Fechar menu</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Sessão</p>
            <p class="mt-2 text-sm font-semibold text-white">{{ Auth::user()->name }}</p>
            <p class="mt-1 text-sm text-slate-400">{{ Auth::user()->email }}</p>
        </div>

        <nav class="mt-6 space-y-2">
            @foreach ($navigationItems as $item)
                <x-sidebar-link :href="$item['route']" :active="$item['active']">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="{{ $item['icon'] }}" />
                        </svg>
                    </x-slot>

                    {{ $item['label'] }}
                </x-sidebar-link>
            @endforeach
        </nav>
    </div>

    <div class="border-t border-white/10 p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl border border-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
            >
                Sair
            </button>
        </form>
    </div>
</aside>
