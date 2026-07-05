@props([
    'padding' => true,
    'mb'      => true,
    'tag'     => 'div',
])

@php
    $classes = 'ig-card';
    if ($padding) $classes .= ' ig-card--padded';
    if ($mb)      $classes .= ' ig-card--mb';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
