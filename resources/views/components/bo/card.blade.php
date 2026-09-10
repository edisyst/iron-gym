@props(['title' => null, 'bodyClass' => 'p-0'])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title !== null || (isset($actions) && $actions->isNotEmpty()))
    <div class="card-header">
        @if ($title !== null)
        <h3 class="card-title">{{ $title }}</h3>
        @endif
        @if (isset($actions) && $actions->isNotEmpty())
        <div class="card-tools">{{ $actions }}</div>
        @endif
    </div>
    @endif
    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
    @if (isset($footer) && $footer->isNotEmpty())
    {{ $footer }}
    @endif
</div>
