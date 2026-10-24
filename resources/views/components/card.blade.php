@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'bg-surface border border-line rounded-[14px] p-5 shadow-sm']) }}>
    @if (isset($header))
        <div class="pb-4 mb-4 border-b border-line">
            {{ $header }}
        </div>
    @elseif ($title || isset($actions))
        <div class="flex items-center justify-between gap-4 pb-4 mb-4 border-b border-line">
            <div>
                @if ($title)
                    <h2 class="text-base font-bold text-ink tracking-tight">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="text-xs text-muted mt-0.5 leading-normal">{{ $description }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if (isset($footer))
        <div class="mt-4 pt-4 border-t border-line flex items-center justify-between text-xs text-muted">
            {{ $footer }}
        </div>
    @endif
</div>

