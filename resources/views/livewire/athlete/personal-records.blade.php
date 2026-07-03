<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Record personali</h2>
    </div>

    @if($records->isEmpty())
        <div class="alert alert-info">
            Nessun record ancora registrato. Completa qualche sessione!
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Esercizio</th>
                                <th class="text-end">e1RM stimato</th>
                                <th class="text-end">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td>
                                        <a href="{{ route('athlete.exercises.show', $record->exercise->slug) }}"
                                           class="text-decoration-none fw-semibold">
                                            {{ $record->exercise->name_it }}
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-warning text-dark fs-6">
                                            {{ number_format($record->value, 1) }} kg
                                        </span>
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ $record->achieved_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            {{ $records->links() }}
        </div>
    @endif
</div>
