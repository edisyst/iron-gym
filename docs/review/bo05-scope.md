# BO05 — Backlog bug feedback, reset filtri, th Azioni

**Aperto:** 2026-09-09
**Chiuso:** 2026-09-09
**Prerequisito:** BO04 completato e chiuso

> Tutti gli item implementati. Suite 624 pass / 6 skipped, PHPStan livello 6 OK, Pint conforme.

---

## Priorità alta

### A1 — BUG: `ExerciseList` usa `session('status')` — feedback mai mostrato

File: `app/Livewire/Backoffice/Exercises/ExerciseList.php` ~63

```php
session()->flash('status', 'Esercizio eliminato.');
```

Il layout `layouts/backoffice.blade.php` gestisce solo `session('success')` e `session('error')`. `session('status')` è ignorato — dopo `deleteExercise()` zero feedback all'utente.

**Fix:** `'status'` → `'success'`. 1 riga.

---

## Priorità media

### B1 — Reset filtri mancante su view con filtri multipli (6 view)

14 view usano `x-bo.filters`. Nessuna ha `resetFilters()` nel PHP né bottone reset nel Blade. L'utente deve cancellare manualmente ogni campo.

View prioritarie (3+ filtri):

| View | Filtri |
|---|---|
| `exercises/exercise-list` | search + muscleGroup + mechanic + skillLevel + equipment (5) |
| `mesocycles/mesocycle-list` | search + status + trainer + athlete (4) |
| `calendar/booking-list` | date + status + trainer + search (4) |
| `admin/feedback-list` | type + dateFrom + dateTo (3) |
| `members/member-list` | search + certFilter (2) |
| `access/access-log-list` | date + search (2) |

**Fix pattern PHP:**
```php
public function resetFilters(): void
{
    $this->reset(['search', 'filterX', 'filterY']);
    $this->resetPage();
}
```

**Fix pattern Blade** (dentro `x-bo.filters`):
```blade
<x-slot name="actions">
    <button wire:click="resetFilters" class="btn btn-default btn-sm">Azzera filtri</button>
</x-slot>
```

---

### B2 — `<th></th>` Azioni senza testo né classe (7 posizioni)

Continuazione BO03-C1. View non coperte:

| File | Posizione |
|---|---|
| `members/member-list.blade.php` | ~45 |
| `mesocycles/mesocycle-list.blade.php` | ~57 |
| `subscriptions/subscription-list.blade.php` | ~43 |
| `templates/template-list.blade.php` | ~49 |
| `reports/training-report.blade.php` | ~34 (colonna Dettaglio) |
| `reports/manager-dashboard.blade.php` | ~140 (colonna Contatta) |
| `members/expiry-dashboard.blade.php` | ~51 e ~120 (2 tabelle) |

**Fix:** → `<th class="text-right table-actions">Azioni</th>` (o etichetta appropriata).

---

## Priorità bassa

### C1 — `feedback-list`: textarea senza label accessibile

File: `resources/views/livewire/backoffice/admin/feedback-list.blade.php` ~54

```blade
<textarea wire:change="saveNotes(...)" placeholder="Note interne…">
```

Solo `placeholder`, nessuna `<label>` né `aria-label`. Screen reader non identifica il campo.

**Fix:** aggiungere `aria-label="Note interne"` al `<textarea>`.

---

## Esclusi dallo scope BO05

| Item | Motivo |
|---|---|
| N+1 queries | Tutti i componenti eager-load correttamente — nessun finding |
| Sortable columns senza UI | `sortBy`/`sortDir` trovati solo dove UI è presente |
| `session()->put()` per flash | Zero occorrenze — tutti usano `flash()` |
| Modal ARIA residui | Tutti conformi post-BO03 |

---

## Note

- A1 è un bug silenzioso ad alto impatto: nessun feedback dopo delete esercizio. Fix 1 riga.
- B1 è meccanico ma richiede modifica sia PHP che Blade per ogni view. Pattern uniforme.
- B2 è continuazione diretta di BO03-C1 — stesse view o view non in scope allora.
- C1 piccolo, 1 attributo.
