@props([
    'variant' => 'primary',
    'type'    => 'button',
    'full'    => false,
    'size'    => 'base',
    'loading' => false,
])

@php
    $classes = 'ig-btn ig-btn--' . $variant;
    if ($full)        $classes .= ' ig-btn--full';
    if ($size === 'sm') $classes .= ' ig-btn--sm';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    <span wire:loading.remove wire:target="{{ $attributes->get('wire:click', '') ?: $attributes->get('wire:submit', '') }}">
        {{ $slot }}
    </span>
    <span wire:loading wire:target="{{ $attributes->get('wire:click', '') ?: $attributes->get('wire:submit', '') }}" aria-hidden="true">
        <span class="ig-spinner"></span>
    </span>
</button>
