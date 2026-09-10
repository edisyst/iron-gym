# BO03 — Backlog UX e accessibilità UI backoffice

**Aperto:** 2026-09-09
**Chiuso:** 2026-09-09
**Prerequisito:** BO02 completato e chiuso

> Tutti gli item implementati. Suite 624 pass / 6 skipped, PHPStan livello 6 OK, Pint conforme.

---

## Priorità alta

### A1 — Flash messages inline duplicati (13 view)

Il layout `layouts/backoffice.blade.php` gestisce già `session('success')` e `session('error')` con Alpine auto-dismiss (4 s) + x-transition. 13 view ri-gestiscono localmente i flash con `<div class="alert">` statici (no auto-dismiss, no transition) — comportamento visivamente incoerente.

**View con flash inline da rimuovere:**

| View | Tipo |
|---|---|
| `mesocycles/mesocycle-detail` | success + error |
| `mesocycles/mesocycle-assign` | success |
| `mesocycles/volume-landmark-manager` | success |
| `reports/financial-report` | success |
| `settings/feature-flag-manager` | success |
| `settings/opening-hours-manager` | success |
| `athletes/body-measurement-form` | success |
| `admin/plate-inventory-manager` | success |
| `calendar/group-class-catalog` | success + error |
| `calendar/class-schedule-manager` | success + error |
| `calendar/group-class-manager` | success |
| + ~2-3 view aggiuntive da verificare in fase di esecuzione | |

**Fix:** rimuovere i blocchi `@if (session(...)) <div class="alert...">` dalle view. Il layout gestisce tutto.

**Nota:** verificare che i flash usino `session()->flash()` (già corretto) e non `session()->put()` (permanente).

---

## Priorità media

### B1 — wire:loading mancante su submit/save (4 view)

4 view hanno bottoni submit/save senza feedback di caricamento — l'utente non sa se il click è stato registrato.

| View | Bottone |
|---|---|
| `templates/template-form` | `<button type="submit">` — nessun wire:loading |
| `exercises/exercise-form` | `<button type="submit">` — nessun wire:loading |
| `mesocycles/volume-landmark-manager` | `wire:click="save"` — nessun wire:loading |
| `messages/message-thread` | `<button type="submit">` — nessun wire:loading |

**Fix:** aggiungere `wire:loading.attr="disabled"` al bottone e spinner `<span wire:loading wire:loading.attr="disabled" class="spinner-border spinner-border-sm mr-1">`.

---

### B2 — Modale Alpine senza attributi ARIA (1 view)

| View | Dettaglio |
|---|---|
| `exercises/exercise-form` | Modale archiviazione: `x-show="open"` con `modal-dialog` — manca `role="dialog"`, `aria-modal="true"`, `aria-labelledby` |

**Fix:** aggiungere `role="dialog" aria-modal="true" aria-labelledby="archiveModalTitle"` al div contenitore; `id="archiveModalTitle"` all'`<h5 class="modal-title">`.

**Riferimento:** CLAUDE.md convenzioni UI → "Modali custom: `role="dialog"` + `aria-modal="true"` + `aria-labelledby`"

---

### B3 — Bottone icon-only senza aria-label (1 view)

| View | Riga | Snippet |
|---|---|---|
| `messages/message-thread` | ~55 | `<button type="submit" class="btn btn-primary btn-sm ml-2"><i class="fas fa-paper-plane"></i></button>` — nessun `aria-label` |

**Fix:** aggiungere `aria-label="Invia messaggio"` al bottone.

**Riferimento:** CLAUDE.md convenzioni UI → "Bottoni icon-only: `aria-label` obbligatorio"

---

## Priorità bassa

### C1 — Colonna Azioni: th senza `table-actions` (5 view)

Pattern canonico: `<th class="text-right">Azioni</th>` + `<td class="table-actions">`.
5 view hanno `<th class="text-right">` ma mancano `table-actions` sul th.

| View | Stato th |
|---|---|
| `calendar/group-class-manager` | `text-right` — manca `table-actions` |
| `calendar/group-class-catalog` | `text-right` — manca `table-actions` |
| `calendar/class-schedule-manager` | `text-right` — manca `table-actions` |
| `calendar/booking-list` | `text-right` — da verificare |
| `exercises/exercise-list` | `table-actions` sul td ma non sul th |

**Fix:** aggiungere `table-actions` ai th delle colonne Azioni.

---

### C2 — Naming inconsistente per chiudi/annulla pannelli inline

4 pattern distinti per la stessa azione (chiudere un pannello/form inline):

| Pattern | View esempio |
|---|---|
| `wire:click="$set('showX', false)"` | class-schedule-manager |
| `wire:click="cancelX()"` | feature-flag-manager |
| `wire:click="closeModal()"` | access-log-list |
| `@click="open = false"` (Alpine) | opening-hours-manager |

Nessun impatto funzionale. Considerare naming convention unificata per BO03 o successivo.

---

## Esclusi dallo scope BO03

| Item | Motivo |
|---|---|
| `admin/feature-flag-manager` (deprecated) | Redirect — nessuna modifica utile |
| `settings/api-docs` | Standalone Swagger — non usa layouts.backoffice |
| Pattern Annulla su navigazione pagina intera (`<a href>`) | Corretto — non è un pannello inline |

---

## Note

- A1 è meccanico e ad alto impatto: 13 view, rimozione di blocchi `@if (session())`. Zero rischio funzionale.
- B1–B3 migliorano UX e accessibilità; B2–B3 rispettano convenzioni già documentate in CLAUDE.md.
- I test esistenti non verificano struttura HTML. I fix BO03 non rompono la suite.
