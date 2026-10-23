@props([
    'title' => 'Terjadi kesalahan',
    'description' => 'Tidak dapat memuat data. Silakan coba kembali.',
    'retryAction' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-8 md:p-10 bg-danger-soft/50 rounded-[14px] border border-red-200']) }}>
    <div class="w-12 h-12 rounded-full bg-danger-soft text-danger flex items-center justify-center mb-4">
        <x-icon name="alert-triangle" size="24" />
    </div>

    <h3 class="text-base font-semibold text-ink mb-1">
        {{ $title }}
    </h3>

    <p class="text-xs md:text-sm text-muted max-w-sm mb-5">
        {{ $description }}
    </p>

    @if ($retryAction)
        <x-button variant="secondary" size="sm" wire:click="{{ $retryAction }}">
            <x-icon name="refresh-cw" size="14" />
            <span>Coba lagi</span>
        </x-button>
    @elseif (isset($action))
        <div>
            {{ $action }}
        </div>
    @endif
</div>

