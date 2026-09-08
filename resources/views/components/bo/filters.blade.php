@props(['title' => 'Filtri'])

<div {{ $attributes->merge(['class' => 'card card-outline card-primary mb-3']) }}>
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
