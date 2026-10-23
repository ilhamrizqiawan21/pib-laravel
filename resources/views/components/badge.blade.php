@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'brand' => 'bg-brand-soft text-brand-strong',
        'success' => 'bg-success-soft text-emerald-700',
        'warning' => 'bg-warning-soft text-amber-700',
        'danger' => 'bg-danger-soft text-danger',
        'neutral' => 'bg-canvas text-muted',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];

    $classes = 'inline-flex items-center font-medium rounded-full ' . ($variants[$variant] ?? $variants['neutral']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>

