@php
    $scrollIntoViewJsSnippet = ($scrollTo ?? 'body') !== false
        ? "(\$el.closest('" . ($scrollTo ?? 'body') . "') || document.querySelector('" . ($scrollTo ?? 'body') . "')).scrollIntoView()"
        : '';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-muted" aria-live="polite">
            Menampilkan
            <span class="font-semibold text-ink">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span>
            dari
            <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
            siswa
        </p>

        <div class="flex items-center gap-1 rounded-xl border border-line bg-canvas/60 p-1 shadow-xs">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted/50" aria-hidden="true"><x-icon name="chevron-left" size="16" /></span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="Halaman sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted transition-colors hover:bg-surface hover:text-brand-strong focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus">
                    <x-icon name="chevron-left" size="16" />
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-xs font-semibold text-muted">{{ $element }}</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-brand-strong px-2 text-xs font-bold text-white shadow-sm">{{ $page }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" aria-label="Ke halaman {{ $page }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-xs font-semibold text-muted transition-colors hover:bg-surface hover:text-brand-strong focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus">{{ $page }}</button>
                            @endif
                        </span>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" aria-label="Halaman berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted transition-colors hover:bg-surface hover:text-brand-strong focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus">
                    <x-icon name="chevron-right" size="16" />
                </button>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted/50" aria-hidden="true"><x-icon name="chevron-right" size="16" /></span>
            @endif
        </div>
    </nav>
@endif
