@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6']) }}>
    <div>
        @if ($eyebrow)
            <div class="text-[11px] font-bold uppercase tracking-[1.3px] text-brand-strong mb-1">
                {{ $eyebrow }}
            </div>
        @endif
        <h1 class="text-2xl sm:text-[26px] md:text-3xl font-bold text-ink tracking-tight leading-tight">
            {{ $title }}
        </h1>
        @if ($description)
            <p class="text-xs sm:text-sm text-muted mt-1 max-w-2xl leading-relaxed">
                {{ $description }}
            </p>
        @endif
    </div>

    @if (isset($actions))
        <div class="page-header-actions flex flex-wrap items-center gap-2 sm:gap-2.5 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>

