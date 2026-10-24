@props([
    'name',
    'title' => null,
    'danger' => false,
])

<div
    x-data="{
        show: false,
        previousFocus: null,
        open() {
            this.previousFocus = document.activeElement;
            this.show = true;
            this.$nextTick(() => this.$refs.panel.querySelector('input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), a[href]')?.focus());
        },
        close() {
            this.show = false;
            this.$nextTick(() => this.previousFocus?.focus());
        },
        keepFocus(event) {
            if (event.key !== 'Tab') return;
            const items = [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])')].filter(item => item.offsetParent !== null);
            if (!items.length) return;
            if (event.shiftKey && document.activeElement === items[0]) {
                event.preventDefault();
                items.at(-1).focus();
            } else if (!event.shiftKey && document.activeElement === items.at(-1)) {
                event.preventDefault();
                items[0].focus();
            }
        }
    }"
    x-show="show"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? open() : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? close() : null"
    x-on:keydown.escape.window="if (show) close()"
    x-on:keydown="keepFocus($event)"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title-{{ $name }}"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
        x-on:click="close()"
    ></div>

    <!-- Panel -->
    <div
        x-ref="panel"
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="modal-panel relative bg-surface rounded-[14px] border border-line shadow-lg overflow-y-auto max-h-[calc(100dvh-3rem)] w-full max-w-lg p-4 sm:p-6 my-8 z-10"
    >
        @if ($title)
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-line">
                <h3 id="modal-title-{{ $name }}" class="text-base font-semibold {{ $danger ? 'text-danger' : 'text-ink' }}">
                    {{ $title }}
                </h3>
                <button type="button" x-on:click="close()" class="text-muted hover:text-ink cursor-pointer p-2 rounded-lg">
                    <x-icon name="x" size="18" />
                    <span class="sr-only">Tutup</span>
                </button>
            </div>
        @endif

        <div class="space-y-4 text-sm text-ink">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="mt-6 pt-4 border-t border-line flex items-center justify-end gap-2.5">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>

