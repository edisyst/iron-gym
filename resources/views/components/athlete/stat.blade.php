@props([
    'label',
    'unit' => null,
])

<div {{ $attributes->merge(['class' => 'ig-stat']) }}>
    <span class="ig-stat__label">{{ $label }}</span>
    <span class="ig-stat__value">
        {{ $slot }}@if($unit)<span class="ig-stat__unit"> {{ $unit }}</span>@endif
    </span>
</div>
