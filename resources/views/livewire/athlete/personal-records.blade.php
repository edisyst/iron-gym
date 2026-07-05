<div>
    <div class="ig-page-header">
        <h1 class="ig-page-title">Record personali</h1>
    </div>

    @if ($records->isEmpty())
        <x-athlete.card>
            <div class="ig-empty-state">
                <svg class="ig-empty-state__icon" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <p class="ig-empty-state__title">Nessun record ancora</p>
                <p class="ig-empty-state__body">Completa qualche sessione per vedere i tuoi PR!</p>
            </div>
        </x-athlete.card>
    @else
        <x-athlete.card :padding="false" :mb="false">
            @foreach ($records as $record)
                <div class="ig-pr-row">
                    <div class="ig-pr-row__info">
                        <a href="{{ route('athlete.exercises.show', $record->exercise->slug) }}"
                           class="ig-pr-row__name">
                            {{ $record->exercise->name_it }}
                        </a>
                        <span class="ig-pr-row__date">
                            {{ $record->achieved_at->format('d/m/Y') }}
                        </span>
                    </div>
                    <x-athlete.stat label="e1RM" unit="kg">
                        {{ number_format($record->value, 1) }}
                    </x-athlete.stat>
                </div>
            @endforeach
        </x-athlete.card>

        @if ($records->hasPages())
            <div style="margin-top:var(--ig-sp-4);">
                {{ $records->links() }}
            </div>
        @endif
    @endif
</div>
