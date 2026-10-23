@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $configs = [
        'info' => [
            'box' => 'bg-slate-50 border-line text-ink',
            'icon' => 'info',
            'iconColor' => 'text-muted',
        ],
        'warning' => [
            'box' => 'bg-warning-soft border-amber-200 text-amber-900',
            'icon' => 'alert-triangle',
            'iconColor' => 'text-warning',
        ],
        'danger' => [
            'box' => 'bg-danger-soft border-red-200 text-red-900',
            'icon' => 'alert-circle',
            'iconColor' => 'text-danger',
        ],
        'success' => [
            'box' => 'bg-success-soft border-emerald-200 text-emerald-900',
            'icon' => 'check-circle',
            'iconColor' => 'text-success',
        ],
    ];

    $config = $configs[$variant] ?? $configs['info'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 p-4 rounded-xl border ' . $config['box']]) }} role="alert">
    <div class="shrink-0 mt-0.5 {{ $config['iconColor'] }}">
        <x-icon :name="$config['icon']" size="18" />
    </div>
    <div class="text-xs md:text-sm">
        @if ($title)
            <div class="font-semibold mb-0.5">{{ $title }}</div>
        @endif
        <div>
            {{ $slot }}
        </div>
    </div>
</div>

