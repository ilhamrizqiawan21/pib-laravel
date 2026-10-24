@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6']) }}>
    <div>
        @if ($eyebrow)
            <div class="text-xs font-semibold uppercase tracking-wider text-brand-strong mb-1">
                {{ $eyebrow }}
            </div>
        @endif
        <h1 class="text-2xl md:text-3xl font-semibold text-ink tracking-tight">
            {{ $title }}
        </h1>
        @if ($description)
            <p class="text-sm text-muted mt-1">
                {{ $description }}
            </p>
        @endif
    </div>

    @if (isset($actions))
        <div class="page-header-actions flex items-center gap-2.5 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>

