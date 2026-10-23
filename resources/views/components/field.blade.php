@props([
    'label' => null,
    'id' => null,
    'error' => null,
    'help' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if($id) for="{{ $id }}" @endif class="block text-xs font-medium text-ink">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
                <span class="sr-only">(wajib)</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($error)
        <p id="{{ $id }}-error" role="alert" class="text-xs text-danger font-medium flex items-center gap-1">
            <x-icon name="alert-circle" size="14" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p class="text-xs text-muted">{{ $help }}</p>
    @endif
</div>

