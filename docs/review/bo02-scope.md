# BO02 — Backlog uniformità UI backoffice

> **CHIUSO (2026-09-09)** — Release BO02 completata. Tutti gli item A e B risolti; item C risultati già conformi alla verifica. Documento punto di partenza storico.

**Aperto:** 2026-09-08  
**Chiuso:** 2026-09-09  
**Prerequisito:** BO01 completato e chiuso

---

## Priorità alta

### A1 — Breadcrumb (tutte le 40 view)

Nessuna view implementa `@section('breadcrumb')`. Il layout `layouts/backoffice.blade.php` ha il mount point predisposto ma non riceve dati. AdminLTE mostra il content-header senza filo di Arianna.

**Lavoro:** implementare `@section('breadcrumb')` per ogni view Livewire — pattern consigliato: prop `$breadcrumbs` array passata dal componente via `layoutData`, renderizzata nel layout. Alternativa: slot Blade dedicato.

**Scope:** 40 view full-page. Non impatta sub-componenti condivisi.

---

### A2 — render() old-style → new-style (10 view rimanenti post-BO01)

Le seguenti view usano ancora `->layout('layouts.backoffice', ['page_title' => '...'])` (old-style):

| View | Stato |
|---|---|
| `athletes/athlete-analytics` | old-style con titolo |
| `athletes/body-measurement-form` | old-style con titolo |
| `admin/feedback-list` | old-style con titolo |
| `mesocycles/mesocycle-detail` | old-style con titolo |
| `mesocycles/mesocycle-list` | old-style con titolo |

**Nota:** 5 view convertite in BO01 Fase 3 (settings-hub, mesocycle-assign, artisan-runner, feature-flag-manager, global-search, volume-landmark-manager). Le restanti non sono state toccate per mantenere i commit atomici.

**Lavoro:** conversione meccanica PHP only, nessun rischio blade.

---

### A3 — Debito di adozione: `x-bo.pagination`

`admin/plate-inventory-manager`: 2 istanze di paginazione raw `@if ($plates->hasPages()) <div class="card-footer">{{ $plates->links() }}</div>`. Il comportamento è corretto ma il markup è manuale.

**Fix:** sostituire con `<x-slot name="footer"><x-bo.pagination :paginator="$plates" /></x-slot>` dentro `x-bo.card`.

---

## Priorità media

### B1 — CTA fuori da ogni card

| View | Problema |
|---|---|
| `exercises/exercise-detail` | Modifica/Archivia in `d-flex` esterno, fuori da qualsiasi card |
| `exercises/exercise-form` | Salva/Annulla in `d-flex` dopo l'ultima card (multi-card form) — da portare in footer slot dell'ultima card o in `div` standalone canonico |
| `calendar/group-class-catalog` | Bottone "Nuovo palinsesto" in `d-flex` row con `<h4>` — fuori da card |

---

### B2 — Filter box non standard

| View | Problema |
|---|---|
| `calendar/group-class-catalog` | Area header è un `div row` con titolo + bottone, non `x-bo.filters` |
| `calendar/group-class-manager` | Filtri inline in `card-tools`, no `x-bo.filters` separato |
| `members/expiry-dashboard` | `x-bo.filters` presente ma con `card-warning` invece di `card-primary` (verifica attuale) |
| `reports/financial-report` | Filter card senza `card-header` "Filtri" |
| `reports/manager-dashboard` | Filter card senza `card-header` "Filtri" |

---

### B3 — Azioni primarie in `card-header d-flex` invece di `card-tools`

| View | Problema |
|---|---|
| `mesocycles/mesocycle-detail` | Bottoni stato mesociclo in `card-header d-flex` |
| `mesocycles/volume-landmark-manager` | Bottoni Salva/Ripristina in `card-header d-flex` |

**Fix:** spostare i bottoni in `card-tools` dentro `card-header`.

---

### B4 — Empty state raw in sezioni drill-down

| View | Problema |
|---|---|
| `athletes/athlete-session-history` | `<p class="text-muted text-center py-4">` nel drilldown espansione (riga 287) |
| `reports/training-report` | `<p class="text-muted">` (×2) nel drilldown (righe 80, 124) |
| `communications/communication-campaign` | `<p class="text-muted mb-0">` nella lista destinatari (riga 142) |

**Nota:** questi contesti sono fuori da tabella — usare `<x-bo.empty>` senza `:colspan`.

---

### B5 — Spacing non standard

| View | Problema |
|---|---|
| `reports/financial-report` | `card-body py-2` nel filter box (altezza ridotta) |
| `reports/manager-dashboard` | `card-body py-2` nel filter box |
| `templates/template-form` | `max-width: 680px` sul wrapper — layout non full-width |

---

## Priorità bassa

### C1 — Empty state `py-3` invece di `py-4`

Alcune view tabella usano `py-3` nell'empty state `<tr><td>`. Le view ora convertite a `x-bo.empty` sono uniformi. Residuo nei casi dove il componente non è stato introdotto (vedere B4 sopra).

### C2 — Paginazione always-on legacy

`access-log-list`, `exercise-list`, `member-list`, `subscription-list`, `template-list` usavano paginazione always-on in assessment. Verificare se BO01 Fase 2 ha completato la conversione a `x-bo.pagination`; se no, aggiungere a BO02.

### C3 — `table-form-card` (template-form)

`template-form` ha `max-width: 680px` come vincolo progettuale. Valutare se il layout ristretto sia appropriato (form breve) o se allinearlo al full-width canonico.

---

## Esclusi dallo scope BO02

| Item | Motivo |
|---|---|
| `artisan-runner` corpo | Canone limitato per scelta — tabella operativa con `table-bordered` semanticamente motivata |
| `feature-flag-manager` corpo | Stesso motivo |
| `admin/feature-flag-manager` (deprecata) | Redirect — nessuna modifica utile |
| `settings/api-docs` | Standalone Swagger — non usa `layouts.backoffice` |
| `trainer-calendar` card-primary | FullCalendar — wrapper col padding vincolato dalla libreria, card-primary semanticamente corretto per il calendario |
| `message-thread` card-primary | Chat layout speciale — mantenuto per enfasi visiva della conversazione |
| Sub-componenti `notification-bell`, `manual-viewer` | Non full-page, fuori scope canone |

---

## Note

- Tutte le esenzioni sono documentate in `docs/architecture/ui-backoffice.md`.
- I test esistenti non verificano struttura HTML (nessun `assertSeeHtml`). I fix BO02 non rompono la suite.
- Priorità A sono meccaniche e a rischio basso. Priorità B richiedono verifica browser post-fix.
