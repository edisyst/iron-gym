# BO04 — Backlog wire:loading inline, ARIA, th Azioni

**Aperto:** 2026-09-09
**Prerequisito:** BO03 completato e chiuso

---

## Priorità alta

### A1 — `communication-campaign`: bottone Invia senza wire:loading

File: `communications/communication-campaign.blade.php` ~97

```blade
<button wire:click="send" wire:confirm="...">
```

Manca `wire:loading.attr="disabled"`. Invio campagna accoda jobs Redis — operazione lenta — bottone resta cliccabile durante attesa.

**Fix:** aggiungere `wire:loading.attr="disabled"` + spinner.

---

### A2 — `booking-list`: td colonna Azioni manca `table-actions`

File: `calendar/booking-list.blade.php` ~92

`<td class="text-right">` — th fu fixato in BO03-C1 ma td corrispondente manca `table-actions`.

**Fix:** → `<td class="text-right table-actions">`.

---

## Priorità media

### B1 — wire:loading mancante su bottoni azione inline tabelle (6 view)

| File | Bottoni |
|---|---|
| `calendar/group-class-catalog.blade.php` | `toggleActive`, `deleteClass` |
| `calendar/class-schedule-manager.blade.php` | `toggleActive`, `deleteSchedule` (+ `save` ha spinner ma no disabled) |
| `calendar/booking-list.blade.php` | `confirm`, `restore` |
| `calendar/group-class-manager.blade.php` | `deleteClass`, `removeParticipant` ×2 (+ `save` ha spinner ma no disabled) |
| `calendar/availability-manager.blade.php` | `deleteSlot`, `deleteOverride` (+ `addSlot`/`addOverride` hanno spinner ma no disabled) |
| `templates/template-builder.blade.php` | `removeSession`, `removeExercise` |
| `exercises/exercise-list.blade.php` | `deleteExercise` |

**Nota:** alcuni bottoni hanno già `wire:loading` per lo spinner ma mancano `wire:loading.attr="disabled"` — doppio rischio.

**Fix:** aggiungere `wire:loading.attr="disabled"` a ogni bottone. Pattern:
```blade
<button wire:click="action" wire:loading.attr="disabled" class="btn btn-sm ...">
    <span wire:loading wire:target="action" class="spinner-border spinner-border-sm mr-1"></span>
    ...
</button>
```

---

### B2 — Bottoni icon-only senza `aria-label` (2 view)

| File | Riga | Bottone |
|---|---|---|
| `calendar/availability-manager.blade.php` | ~67 | `deleteSlot` — `<i class="fas fa-trash">` senza label |
| `calendar/availability-manager.blade.php` | ~165 | `deleteOverride` — `<i class="fas fa-trash">` senza label |
| `templates/template-builder.blade.php` | ~59 | `removeSession` — `<i class="fas fa-trash">` senza label |
| `templates/template-builder.blade.php` | ~90 | `removeExercise` — `<i class="fas fa-times">` senza label |

**Fix:** aggiungere `aria-label` descrittivo a ogni bottone.

**Riferimento:** CLAUDE.md → "Bottoni icon-only: `aria-label` obbligatorio"

---

### B3 — `availability-manager`: 2 `<th>` Azioni senza testo né classi

| File | Riga | Attuale |
|---|---|---|
| `calendar/availability-manager.blade.php` | ~57 | `<th></th>` (tabella slot) |
| `calendar/availability-manager.blade.php` | ~147 | `<th></th>` (tabella override) |

**Fix:** → `<th class="text-right table-actions">Azioni</th>`.

---

## Priorità bassa

### C1 — `communication-campaign`: alert `$sent` non auto-dismissing

File: `communications/communication-campaign.blade.php` 2-7

```blade
@if ($sent)
    <div class="alert alert-success">...Campagna inviata in coda...</div>
@endif
```

Non è `session()` quindi il layout non lo gestisce. Rimane visibile fino al prossimo render/navigate.

**Opzioni:**
- Alpine auto-dismiss inline (x-data + x-show + setTimeout)
- `$this->dispatch()` verso evento globale se il layout lo ascolta

**Fix:** aggiungere Alpine auto-dismiss: `x-data x-init="setTimeout(() => $el.remove(), 4000)"`.

---

### C2 — `booking-list`: `confirm`/`restore` senza `wire:confirm`

File: `calendar/booking-list.blade.php` ~95, ~107

`confirm(id)` e `restore(id)` cambiano status prenotazione senza dialogo di conferma. `deleteClass` e `deleteSchedule` hanno `wire:confirm`; queste due no — inconsistente.

**Fix:** aggiungere `wire:confirm="Confermare questa azione?"` ai bottoni `confirm` e `restore`.

---

## Esclusi dallo scope BO04

| Item | Motivo |
|---|---|
| `wire:model` nudo su text input | Livewire 3: deferred di default — nessun roundtrip su keypress |
| `x-bo.card bodyClass="p-0"` senza tabella | Tutti i 14 casi hanno tabella interna — conformi |
| Modal ARIA (communication-campaign, booking-list) | Già conformi |
| `dd()`/`dump()` residui | Zero trovati |

---

## Note

- B1 è meccanico ad alta copertura: ~12 bottoni in 6-7 view. Pattern uniforme.
- B2+B3 piccoli, stessa view `availability-manager` — conviene fare insieme.
- C1 scelta design: Alpine inline è più semplice, dispatch richiede modifica layout.
- C2 è decisione prodotto: se `confirm`/`restore` sono reversibili, `wire:confirm` può essere omesso.
