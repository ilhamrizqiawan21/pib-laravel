@props([
    'rows' => 3,
])

<div {{ $attributes->merge(['class' => 'space-y-3 animate-pulse']) }}>
    @for ($i = 0; $i < $rows; $i++)
        <div class="h-10 bg-slate-200/70 rounded-lg w-full"></div>
    @endfor
</div>

