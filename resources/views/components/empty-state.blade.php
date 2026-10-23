@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-8 md:p-12 bg-surface rounded-[14px] border border-dashed border-line']) }}>
    <div class="w-12 h-12 rounded-full bg-brand-soft text-brand-strong flex items-center justify-center mb-4">
        <x-icon :name="$icon === 'inbox' ? 'file-text' : $icon" size="24" />
    </div>

    <h3 class="text-base font-semibold text-ink mb-1">
        {{ $title }}
    </h3>

    @if ($description)
        <p class="text-xs md:text-sm text-muted max-w-sm mb-5">
            {{ $description }}
        </p>
    @endif

    @if ($actionLabel && $actionUrl)
        <x-button :href="$actionUrl" variant="primary" size="sm">
            <x-icon name="plus" size="16" />
            <span>{{ $actionLabel }}</span>
        </x-button>
    @elseif (isset($action))
        <div class="mt-2">
            {{ $action }}
        </div>
    @endif
</div>

