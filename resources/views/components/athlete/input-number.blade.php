@props([
    'mode'        => 'numeric',
    'min'         => '0',
    'max'         => null,
    'step'        => null,
    'placeholder' => null,
    'stepper'     => false,
    'label'       => null,
])

@php
    $wrapClass = $stepper ? 'ig-num-input' : 'ig-num-input ig-num-input--solo';
@endphp

<div x-data class="{{ $wrapClass }}">
    @if($stepper)
    <button
        type="button"
        class="ig-num-input__step"
        aria-label="Diminuisci"
        @click="
            const f = $refs.numInput;
            const step = parseFloat(f.step) || 1;
            const val = parseFloat(f.value) || 0;
            const min = f.min !== '' ? parseFloat(f.min) : -Infinity;
            f.value = Math.max(min, +(val - step).toFixed(4));
            f.dispatchEvent(new Event('input', { bubbles: true }));
            f.dispatchEvent(new Event('change', { bubbles: true }));
        "
    >−</button>
    @endif

    <input
        x-ref="numInput"
        type="number"
        inputmode="{{ $mode }}"
        min="{{ $min }}"
        @if($max) max="{{ $max }}" @endif
        @if($step) step="{{ $step }}" @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => 'ig-num-input__field']) }}
    >

    @if($stepper)
    <button
        type="button"
        class="ig-num-input__step"
        aria-label="Aumenta"
        @click="
            const f = $refs.numInput;
            const step = parseFloat(f.step) || 1;
            const val = parseFloat(f.value) || 0;
            const max = f.max !== '' ? parseFloat(f.max) : Infinity;
            f.value = Math.min(max, +(val + step).toFixed(4));
            f.dispatchEvent(new Event('input', { bubbles: true }));
            f.dispatchEvent(new Event('change', { bubbles: true }));
        "
    >+</button>
    @endif
</div>
