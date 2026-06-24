@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-medium text-white/55 uppercase tracking-wide mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
