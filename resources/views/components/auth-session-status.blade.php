@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-green-500/20 bg-green-500/10 px-4 py-2.5 text-sm text-green-400']) }}>
        {{ $status }}
    </div>
@endif
