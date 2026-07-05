@props(['lines' => 3, 'height' => null])
<div class="ig-skeleton" aria-hidden="true" aria-label="Caricamento...">
    @for ($i = 0; $i < $lines; $i++)
        <div class="ig-skeleton__line {{ $i === 0 ? 'ig-skeleton__line--wide' : '' }}"
             @if ($height && $i === 0) style="height:{{ $height }}" @endif></div>
    @endfor
</div>
