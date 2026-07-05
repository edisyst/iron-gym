@props([
    'status' => 'gray',
])

<span {{ $attributes->merge(['class' => 'ig-badge ig-badge--' . $status]) }}>
    {{ $slot }}
</span>
