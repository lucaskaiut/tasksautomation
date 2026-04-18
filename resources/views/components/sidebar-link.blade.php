@props(['active' => false])

@php
    $classes = $active
        ? 'bg-white text-slate-950 shadow-sm'
        : 'text-slate-300 hover:bg-white/10 hover:text-white';
@endphp

<a {{ $attributes->class([
    'group flex items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-semibold transition',
    $classes,
]) }}>
    <span @class([
        'flex h-9 w-9 items-center justify-center rounded-2xl transition',
        'bg-slate-950 text-white' => $active,
        'bg-white/10 text-slate-300 group-hover:bg-white/15 group-hover:text-white' => ! $active,
    ])>
        {{ $icon }}
    </span>

    <span>{{ $slot }}</span>
</a>
