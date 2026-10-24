<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now();
            this.toasts.push({ id, ...toast });
            setTimeout(() => this.remove(id), toast.duration || 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-init="
        @if (session()->has('success'))
            add({ type: 'success', message: @js(session('success')) });
        @endif
        @if (session()->has('error'))
            add({ type: 'danger', message: @js(session('error')) });
        @endif
    "
    x-on:toast.window="add($event.detail)"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm pointer-events-none"
    role="status"
    aria-live="polite"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="pointer-events-auto flex items-start gap-3 p-3.5 rounded-xl border shadow-md bg-surface border-line text-sm text-ink"
            :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-900': t.type === 'success',
                'border-amber-200 bg-amber-50 text-amber-900': t.type === 'warning',
                'border-red-200 bg-red-50 text-red-900': t.type === 'danger',
            }"
        >
            <div class="shrink-0 mt-0.5">
                <template x-if="t.type === 'success'"><x-icon name="check-circle" size="18" class="text-emerald-600" /></template>
                <template x-if="t.type === 'danger'"><x-icon name="alert-circle" size="18" class="text-danger" /></template>
                <template x-if="t.type === 'warning'"><x-icon name="alert-triangle" size="18" class="text-warning" /></template>
                <template x-if="!t.type || t.type === 'info'"><x-icon name="info" size="18" class="text-muted" /></template>
            </div>
            <div class="flex-1 text-xs md:text-sm" x-text="t.message"></div>
            <button type="button" @click="remove(t.id)" class="text-muted hover:text-ink cursor-pointer p-0.5 shrink-0">
                <x-icon name="x" size="14" />
            </button>
        </div>
    </template>
</div>

