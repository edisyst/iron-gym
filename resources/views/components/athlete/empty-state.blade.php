@props([
    'title',
    'body'  => null,
    'href'  => null,
    'cta'   => null,
])
<div class="ig-empty" role="status">
    @if ($slot->isNotEmpty())
        <div class="ig-empty__icon" aria-hidden="true">{{ $slot }}</div>
    @endif
    <p class="ig-empty__title">{{ $title }}</p>
    @if ($body)
        <p class="ig-empty__body">{{ $body }}</p>
    @endif
    @if ($href && $cta)
        <a href="{{ $href }}" class="ig-btn ig-btn--secondary ig-empty__cta">{{ $cta }}</a>
    @endif
</div>
