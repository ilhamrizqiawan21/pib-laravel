@props([
    'value' => 0,
    'max' => 100,
    'showLabel' => false,
])

@php
    $percentage = $max > 0 ? min(100, max(0, round(($value / $max) * 100))) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($showLabel)
        <div class="flex justify-between items-center text-xs text-muted mb-1.5 font-medium">
            <span>Progres</span>
            <span>{{ $percentage }}%</span>
        </div>
    @endif
    <div class="w-full bg-line rounded-full h-2 overflow-hidden" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
        <div class="bg-brand h-full rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
    </div>
</div>

