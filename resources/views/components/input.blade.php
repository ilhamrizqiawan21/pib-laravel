@props([
    'type' => 'text',
    'hasError' => false,
])

@php
    $baseClasses = 'w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand disabled:bg-canvas disabled:text-muted disabled:cursor-not-allowed';
    $borderClasses = $hasError ? 'border-danger focus-visible:ring-danger/20' : 'border-line';
@endphp

<input
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "$baseClasses $borderClasses"]) }}
/>

