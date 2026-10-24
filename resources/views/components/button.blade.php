@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'loading' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-focus';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2 text-sm gap-2',
        'lg' => 'px-5 py-2.5 text-base gap-2.5',
    ];

    $variants = [
        'primary' => 'bg-brand-strong text-white hover:bg-brand-strong active:bg-brand-strong',
        'secondary' => 'bg-surface border border-line text-ink hover:bg-canvas active:bg-line',
        'outline' => 'bg-transparent border border-line text-ink hover:bg-canvas active:bg-line',
        'ghost' => 'text-muted hover:text-ink hover:bg-canvas active:bg-line',
        'danger' => 'bg-danger text-white hover:bg-red-600 active:bg-red-700',
        'danger-ghost' => 'text-danger hover:bg-danger-soft active:bg-red-100',
    ];

    $classes = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <x-icon name="loader-2" class="animate-spin -ml-0.5 mr-1.5" size="16" />
        @endif
        {{ $slot }}
    </button>
@endif

