@props(['colspan' => null])

@if ($colspan !== null)
<tr><td colspan="{{ $colspan }}" class="text-center text-muted py-4">{{ $slot }}</td></tr>
@else
<div class="text-center text-muted py-4">{{ $slot }}</div>
@endif
