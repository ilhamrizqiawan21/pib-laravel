@props([
    'status' => 'active',
    'label' => null,
])

@php
    $statusKey = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $configs = [
        'active' => [
            'variant' => 'success',
            'dot' => 'bg-emerald-500',
            'defaultLabel' => 'Aktif',
        ],
        'inactive' => [
            'variant' => 'neutral',
            'dot' => 'bg-slate-400',
            'defaultLabel' => 'Nonaktif',
        ],
        'pending' => [
            'variant' => 'warning',
            'dot' => 'bg-amber-500',
            'defaultLabel' => 'Menunggu',
        ],
        'error' => [
            'variant' => 'danger',
            'dot' => 'bg-red-500',
            'defaultLabel' => 'Galat',
        ],
        // CompletionStatus Enums
        'Materi belum tersedia' => [
            'variant' => 'neutral',
            'dot' => 'bg-slate-400',
            'defaultLabel' => 'Materi belum tersedia',
        ],
        'Belum dinilai' => [
            'variant' => 'neutral',
            'dot' => 'bg-slate-400',
            'defaultLabel' => 'Belum dinilai',
        ],
        'Sebagian' => [
            'variant' => 'warning',
            'dot' => 'bg-amber-500',
            'defaultLabel' => 'Sebagian',
        ],
        'Lengkap' => [
            'variant' => 'success',
            'dot' => 'bg-emerald-500',
            'defaultLabel' => 'Lengkap',
        ],
    ];

    $config = $configs[$statusKey] ?? [
        'variant' => 'neutral',
        'dot' => 'bg-slate-400',
        'defaultLabel' => $statusKey,
    ];
    $displayLabel = $label ?? $config['defaultLabel'];
@endphp

<x-badge :variant="$config['variant']" {{ $attributes }}>
    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $config['dot'] }}"></span>
    {{ $displayLabel }}
</x-badge>

