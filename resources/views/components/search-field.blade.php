@props([
    'placeholder' => 'Cari...',
    'shortcut' => '/',
])

<div {{ $attributes->merge(['class' => 'relative flex items-center']) }}>
    <span class="absolute left-3 text-muted pointer-events-none">
        <x-icon name="search" size="16" />
    </span>

    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except('class')->merge(['class' => 'w-full rounded-lg border border-line bg-surface pl-9 pr-10 py-2 text-sm text-ink placeholder:text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand']) }}
    />

    @if ($shortcut)
        <span class="absolute right-3 pointer-events-none">
            <x-kbd>{{ $shortcut }}</x-kbd>
        </span>
    @endif
</div>

