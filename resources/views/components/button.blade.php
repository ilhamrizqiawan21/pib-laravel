@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'loading' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-xl transition-all duration-150 cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-focus active:scale-[0.98]';

    $sizes = [
        'xs' => 'px-2.5 py-1 text-xs gap-1.5 min-h-[30px]',
        'sm' => 'px-3 py-1.5 text-xs gap-1.5 min-h-[34px]',
        'md' => 'px-4 py-2 text-sm gap-2 min-h-[40px]',
        'lg' => 'px-5 py-2.5 text-base gap-2.5 min-h-[46px]',
    ];

    $variants = [
        'primary' => 'bg-brand-strong hover:bg-brand active:bg-brand-strong text-white shadow-xs hover:shadow-sm border border-transparent',
        'secondary' => 'bg-surface border border-line text-ink hover:bg-canvas hover:border-slate-300 active:bg-line/70 shadow-2xs hover:shadow-xs',
        'outline' => 'bg-transparent border border-line text-ink hover:bg-canvas hover:border-slate-300 active:bg-line/70',
        'ghost' => 'bg-transparent text-muted hover:text-ink hover:bg-canvas active:bg-line/60',
        'danger' => 'bg-danger hover:bg-red-600 active:bg-red-700 text-white shadow-xs hover:shadow-sm border border-transparent',
        'danger-ghost' => 'bg-transparent text-danger hover:bg-danger-soft active:bg-red-100',
    ];

    $classes = $baseClasses . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{!! $href !!}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <x-icon name="loader-2" class="animate-spin -ml-0.5 mr-1.5 shrink-0" size="16" />
        @endif
        {{ $slot }}
    </button>
@endif

