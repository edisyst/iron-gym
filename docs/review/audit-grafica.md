# Audit Grafico Backoffice — Iron Gym

**Data audit:** 2026-06-28  
**Data completamento Fase 1+2:** 2026-06-28  
**Scope:** Layout, view Blade, componenti Livewire, AdminLTE 3.x  
**Stato:** COMPLETATO — Fase 1 (coerenza) + Fase 2 (brand identity) applicate

## Riepilogo interventi

| Finding | Severità | Stato | Commit |
|---|---|---|---|
| H1 — 3 sistemi CSS | HIGH | Fase 2 ✅ — brand layer unifica backoffice | `2ba073d`, `3a5366b`, `fecf203` |
| H2 — Modali non ARIA | HIGH | ✅ Risolto Fase 1 | `d459406` |
| H3 — Bottoni icon-only | HIGH | ✅ Risolto Fase 1 | `745d2bb` |
| H4 — Athlete non responsive | HIGH | Rimandato (richiede refactor layout athlete) | — |
| M1 — btn-xs non standard | MED | ✅ Risolto Fase 1 | `76555d9` |
| M2 — Doppio pattern errori | MED | ✅ Risolto Fase 1 | `2dd49ff` |
| M3 — Loading state misto | MED | Bassa priorità, rinviato | — |
| M4 — Badge athlete custom | MED | Rinviato (Fase 2 athlete non in scope) | — |
| M5 — Inline style filtri | MED | ✅ Risolto Fase 1 | `6a4a87c` |
| M6 — Skip link + contrasto | MED | ✅ Skip link Fase 1; contrasto athlete rinviato | `b3062bb` |
| L1-L5 — Finding LOW | LOW | Rinviati / non critici | — |

---

## 1. Inventario Layout e Struttura

### Layout principali

| File | Scopo | Base |
|---|---|---|
| `resources/views/layouts/backoffice.blade.php` | Layout unico backoffice | Estende `adminlte::page` |
| `resources/views/layouts/athlete.blade.php` | Layout area atleta | Custom HTML/CSS inline dark theme |
| `resources/views/layouts/app.blade.php` | Legacy navigation | Tailwind |
| `resources/views/layouts/guest.blade.php` | Pagine auth | Tailwind |

### Componenti Blade (resources/views/components/)

Tutti i componenti `input-error`, `input-label`, `primary-button`, `text-input`, `auth-session-status` usano **Tailwind** — sistema completamente diverso da AdminLTE.

---

## 2. Inventario Livewire Components

**Totale:** 48 componenti (22 Backoffice, 15 Athlete, 3 Shared, 8 Admin/Report/Other)

### Backoffice (app/Livewire/Backoffice/)

| Componente | View | Tipo | Area |
|---|---|---|---|
| `Dashboard` | backoffice/dashboard | Full-page | Gestionale |
| `Members/MemberList` | members/member-list | Full-page | Tesserati |
| `Members/MemberForm` | members/member-form | Full-page | Tesserati |
| `Exercises/ExerciseList` | exercises/exercise-list | Full-page | Esercizi |
| `Exercises/ExerciseForm` | exercises/exercise-form | Full-page | Esercizi |
| `Exercises/ExerciseDetail` | exercises/exercise-detail | Full-page | Esercizi |
| `Templates/TemplateList` | templates/template-list | Full-page | Schede |
| `Templates/TemplateForm` | templates/template-form | Full-page | Schede |
| `Templates/TemplateBuilder` | templates/template-builder | Full-page | Schede |
| `Subscriptions/SubscriptionList` | subscriptions/subscription-list | Full-page | Abbonamenti |
| `Subscriptions/SubscriptionForm` | subscriptions/subscription-form | Full-page | Abbonamenti |
| `Calendar/TrainerCalendar` | calendar/trainer-calendar | Full-page | Calendario |
| `Calendar/AvailabilityManager` | calendar/availability-manager | Full-page | Calendario |
| `Calendar/BookingList` | calendar/booking-list | Full-page | Prenotazioni |
| `Calendar/GroupClassManager` | calendar/group-class-manager | Full-page | Corsi |
| `Mesocycles/MesocycleList` | mesocycles/mesocycle-list | Full-page | Training |
| `Mesocycles/MesocycleDetail` | mesocycles/mesocycle-detail | Full-page | Training |
| `Mesocycles/MesocycleAssign` | mesocycles/mesocycle-assign | Full-page | Training |
| `Mesocycles/VolumeLandmarkManager` | mesocycles/volume-landmark-manager | Full-page | Training |
| `Reports/FinancialReport` | reports/financial-report | Full-page | Report |
| `Reports/TrainingReport` | reports/training-report | Full-page | Report |
| `Reports/ManagerDashboard` | reports/manager-dashboard | Full-page | Report |
| `Admin/FeatureFlagManager` | admin/feature-flag-manager | Full-page | Admin |
| `Admin/FeedbackList` | admin/feedback-list | Full-page | Admin |

### Athlete (app/Livewire/Athlete/)

| Componente | View | Tipo |
|---|---|---|
| `Dashboard` | athlete/dashboard | Full-page |
| `TrainingHub` | athlete/training-hub | Full-page |
| `WorkoutSession` | athlete/workout-session | Full-page |
| `History` | athlete/history | Full-page |
| `Progress` | athlete/progress | Full-page |
| `ExerciseCatalog` | athlete/exercise-catalog | Full-page |
| `ExerciseDetail` | athlete/exercise-detail | Full-page |
| `Booking` | athlete/booking | Full-page |
| `Messages` | athlete/messages | Full-page |
| `Profile` | athlete/profile | Full-page |
| `BodyMeasurementForm` | athlete/body-measurement-form | Full-page |
| `SessionFeedbackForm` | athlete/session-feedback-form | Full-page |
| `ProgressPhotoUpload` | athlete/progress-photo-upload | Full-page |

### Shared (app/Livewire/Shared/)

| Componente | View | Tipo |
|---|---|---|
| `InAppFeedback` | shared/in-app-feedback | Embedded |
| `NotificationBell` | shared/notification-bell | Embedded |

---

## 3. Navigazione

### Backoffice Sidebar (config/adminlte.php)

```
Dashboard                       [fa-tachometer-alt]

GESTIONALE
  Tesserati                     [fa-users]
  Abbonamenti                   [fa-id-card]
  Accessi                       [fa-door-open]

TRAINING
  Esercizi                      [fa-dumbbell]
  Schede Template               [fa-clipboard-list]
  Mesocicli                     [fa-calendar-alt]
  Report Allenamento            [fa-chart-bar]
  Storico Atleti                [fa-history]

CALENDARIO
  Calendario                    [fa-calendar-alt]
  Disponibilita                 [fa-clock]
  Prenotazioni PT               [fa-user-clock]
  Corsi Collettivi              [fa-users] (gate: view-group-classes)

COMUNICAZIONE
  Campagne                      [fa-bullhorn]
```

Config: `sidebar_nav_accordion: true`, `layout_topnav: false`.

### Athlete Bottom Nav (athlete.blade.php)

5 tab fissi: Oggi (dashboard), Storico, Esercizi, Prenota, Messaggi (badge unread via Alpine fetch).

---

## 4. Uso Componenti AdminLTE 3

### Componenti CSS usati

| Componente | Dove | Coerenza |
|---|---|---|
| `small-box` | Dashboard (4 KPI: tesserati attivi, abbonamenti in scadenza, accessi oggi, certificati) | Buona |
| `card` / `card-header` / `card-body` / `card-footer` | Tutti i moduli backoffice | Buona |
| `card-outline card-primary` | trainer-calendar, training-report | Usato raramente, non sistematico |
| `card-outline card-warning` | group-class-manager | Isolato, coerente localmente |
| `callout` | mesocycle-detail (deload signal) | Isolato ma corretto |
| `table table-hover table-striped` | Tutte le liste | Buona |
| `badge-success/danger/warning/info/secondary` | Status in liste | Buona |
| Modal custom `.modal.fade.show.d-block` | trainer-calendar, access-log-list, feature-flag-manager | Senza semantica ARIA |

### Componenti AdminLTE Blade NON usati

`x-adminlte-card`, `x-adminlte-callout`, `x-adminlte-info-box`, `x-adminlte-badge`, `x-adminlte-modal` — tutti disabilitati in config (`livewire: false`). Uso diretto delle classi CSS Bootstrap/AdminLTE invece delle direttive Blade.

**Proposta:** per coerenza con future evoluzioni, valutare se abilitare i componenti Blade AdminLTE o mantenere classi CSS dirette (scelta attuale è valida se coerente).

---

## 5. Incoerenze Visive

### 5.1 Tre sistemi di design nella stessa app

| Area | Framework | Palette | Border radius |
|---|---|---|---|
| Backoffice | Bootstrap 4 + AdminLTE 3.x | Blu AdminLTE default | 0 (inherited) |
| Athlete | Custom HTML/CSS inline (dark) | Arancio #FF6B00, bg #121212 | 8–12px |
| Auth | Tailwind | Rosso red-600 | rounded-xl |

**Severita: HIGH**  
**Proposta:** Nella Fase 2 definire un layer CSS custom che unifica brand color, radius e font sopra AdminLTE senza forkarli; auth pages andrebbero migrate dallo stack Tailwind a Blade AdminLTE (o almeno allineate cromaticamente).

### 5.2 Bottoni inconsistenti

| Classe | Problema | File |
|---|---|---|
| `btn-xs` | Non standard Bootstrap 4 (deprecato) | member-list, exercise-list, feature-flag-manager |
| `btn btn-primary btn-sm` vs `btn-sm` | A volte manca la classe base `btn` | Vari |
| `btn-outline-info` vs `btn-outline-secondary` | Uso alternato senza criterio | Vari |
| `.btn-accent` (athlete) | Classe custom, non riusa Bootstrap | athlete/* |
| `.btn-ghost` (athlete) | Classe custom | athlete/* |

**Severita: MED**  
**Proposta:** Standardizzare su `btn-sm` (rimuovere `btn-xs`); documentare mapping azioni→variante (primary=salva, success=conferma, danger=elimina, secondary=annulla); creare `.btn-accent` come alias Bootstrap con CSS custom.

### 5.3 Badge inconsistenti

Backoffice usa classi Bootstrap (`badge-success`, `badge-danger`, etc.). Athlete usa classi custom (`.athlete-badge`, colori Tailwind `#22c55e`, `#ef4444`). Non intercambiabili.

**Severita: MED**  
**Proposta:** Unificare su classi Bootstrap in entrambe le aree; l'area athlete può mantenere stile dark con `background` CSS custom ma nomi classe condivisi.

### 5.4 Form e feedback errori

Coesistono due pattern:

```
// Pattern A (corretto Bootstrap): 96% dei form
<input class="form-control @error('f') is-invalid @enderror">
@error('f') <span class="invalid-feedback">{{ $message }}</span> @enderror

// Pattern B (custom): group-class-manager e altri
<input class="form-control form-control-sm">
@error('f') <span class="text-danger small">{{ $message }}</span> @enderror
```

**Severita: MED**  
**Proposta:** Normalizzare a Pattern A ovunque; creare partial Blade `@include('partials.field-error', ['field' => '...'])` per evitare duplicazione.

### 5.5 Stati empty/loading

| Stato | Pattern attuale |
|---|---|
| Empty list | `@forelse @empty <td colspan="N" class="text-center text-muted">Nessun risultato</td>` — coerente |
| Loading spinner | `wire:loading <span class="spinner-border spinner-border-sm">` — in alcuni |
| Loading disabled | `wire:loading.attr="disabled"` su bottone — in altri |
| Loading text swap | `wire:loading.remove` + `wire:loading` — in athlete |
| Skeleton loader | Non presente |

Tre pattern diversi per lo stesso effetto. Nessun skeleton/placeholder.

**Severita: MED**  
**Proposta:** Standardizzare su `wire:loading.attr="disabled"` + spinner inline sul bottone; aggiungere un pattern minimo per tabelle (3 righe placeholder grigie).

### 5.6 Inline style che rompono responsivita

File con `style="width: Npx"` su input/select filtri:

- `member-list.blade.php` — `style="width:200px"`
- `exercise-list.blade.php` — selects con width fissi
- Altri list component

**Severita: MED**  
**Proposta:** Migrare a classi Bootstrap (`w-auto`, `col-md-3`, `col-sm-12`) o a classi utilitarie AdminLTE.

### 5.7 Uso di card-outline non sistematico

`card-outline card-primary` appare in 3-4 file senza regola chiara (a volte per sezioni calendario, a volte per report). Le altre card sono standard senza outline.

**Severita: LOW**  
**Proposta:** Definire regola: card-outline per sezioni "attive/correnti" (sessione in corso, prenotazione odierna); card standard per tutto il resto.

---

## 6. Tabelle Dati

**Configurazione globale:** DataTables JS `active: false` in `config/adminlte.php`. Paginazione interamente via Livewire `->paginate()`.

| Tabella | File | Filtri | Paginazione | Sorting |
|---|---|---|---|---|
| Member List | members/member-list | search, status, cert_issues | Livewire | Assente |
| Exercise List | exercises/exercise-list | search, muscleGroup, mechanic, skillLevel, equipment | Livewire | Assente |
| Subscription List | subscriptions/subscription-list | search, member, plan, status | Livewire | Assente |
| Template List | templates/template-list | search, goal, active/archived | Livewire | Assente |
| Access Log | access-log-list | dateFilter, search | Livewire | Assente |
| Training Report | reports/training-report | dateFrom, dateTo, mesoStatus | Livewire | Assente |
| Financial Report | reports/financial-report | year | No (aggregato) | N/A |
| Feature Flags | admin/feature-flag-manager | Nessuno | Nessuna | Assente |

**Severita: LOW** — DataTables disabilitato, Livewire server-side corretto. Sorting assente ma non critico nell'MVP.

---

## 7. Alpine.js e JS Custom

### Alpine.js — pattern usati

| Pattern | File | Corretto |
|---|---|---|
| `x-data="{ show: true }" x-init="setTimeout..."` | backoffice.blade.php | Si — auto-dismiss flash alert |
| `x-data="{ locked: true }" :readonly="locked"` | exercise-form.blade.php | Si — toggle slug edit |
| `x-show`, `@click="open = !open"` | Molteplici | Si — dropdown/toggle |
| `x-text="unread"`, `x-show="unread > 0"` | athlete.blade.php | Si — badge messaggi |
| `fetch()` per conteggio messaggi | athlete.blade.php | Funziona ma accoppia JS a URL hard-coded |

### JS inline da migrare

| Problema | File | Proposta |
|---|---|---|
| `onmouseover="this.style.background=..."` | athlete/exercise-catalog | CSS `:hover` o Alpine `@mouseenter` |
| `onclick="event.preventDefault()..."` su logout | athlete.blade.php | `wire:navigate` o Alpine `@click.prevent` |
| `fetch('/athlete/messages/unread-count')` inline | athlete.blade.php | Alpine store oppure Livewire polling |

**Severita: LOW** — Funziona; migrazione a Alpine migliora manutenibilita.

---

## 8. Responsivita e Accessibilita

### 8.1 Responsivita

**Backoffice:**
- Grid Bootstrap corretta (`col-lg-3 col-6` per dashboard KPI, `col-md-6` per form).
- Problema: `style="width: Npx"` su filtri input/select non si adatta su viewport <768px.
- Tabelle: nessun overflow-x su mobile; colonne si comprimono ma non stackano.

**Athlete layout:**
- Max-width 600px centrato — funziona per mobile, ma non si espande su tablet/desktop.
- Header fisso 48px + bottom nav fisso 72px — corretti su mobile.
- Nessuna media query per breakpoint tablet (768px).
- Inline CSS direttamente in `athlete.blade.php` (difficile da manutenere).

**Severita: HIGH** per athlete su tablet; **MED** per backoffice su mobile.

**Proposta:**
- Athlete: aggiungere CSS file separato in `public/css/athlete.css` o `resources/css/athlete.css` con media queries.
- Backoffice: rimuovere inline width su filtri.

### 8.2 Accessibilita

**Buone pratiche presenti:**
- `<label for="id">` associato agli input: presente nella grande maggioranza dei form.
- `alt="{{ $exercise->name_it }}"` su immagini esercizi.
- Tipi input semantici (`type="email"`, `type="date"`, `type="datetime-local"`).
- Uso di `<strong>`, `<code>`, `<small>` per gerarchia testuale.

**Problemi rilevati:**

| Problema | File | Severita |
|---|---|---|
| Modal custom senza `role="dialog"`, `aria-labelledby`, `aria-describedby` | trainer-calendar, access-log-list, feature-flag-manager, booking-list | HIGH |
| Nessun focus trap nelle modali | Stessi file sopra | HIGH |
| Button con solo icona senza `title` o `aria-label` | Tutte le action column nelle tabelle | HIGH |
| Nessun skip link | backoffice.blade.php, athlete.blade.php | MED |
| Contrasto #FF6B00 su #1E1E1E — borderline WCAG AA (4.5:1) | athlete.blade.php, tutti athlete/* | MED |
| `aria-*` assenti su componenti custom (dropdown Alpine, accordion) | template-builder, athlete nav | LOW |

**Proposta:**
- Sprint 1: aggiungere `role="dialog"`, `aria-labelledby` a tutte le modali; aggiungere `aria-label` ai bottoni icon-only.
- Sprint 2: implementare focus trap via Alpine (`x-trap` plugin disponibile).
- Sprint 2: aggiungere skip link in entrambi i layout.

---

## 9. Sintesi Finding per Priorita

### HIGH — Intervento immediato

| # | Categoria | Descrizione | File coinvolti |
|---|---|---|---|
| H1 | Sistema di design | Tre sistemi distinti (AdminLTE + Custom dark + Tailwind) nella stessa app | layouts/*, components/*, athlete/* |
| H2 | Accessibilita | Modali custom prive di semantica ARIA (no role, no aria-labelledby, no focus trap) | trainer-calendar, access-log-list, feature-flag-manager, booking-list |
| H3 | Accessibilita | Bottoni icon-only senza `aria-label` o `title` | Tutte le tabelle backoffice |
| H4 | Responsivita | Layout athlete non responsive su tablet (max-width fisso 600px, no media queries 768px+) | athlete.blade.php, athlete/* |

### MED — Sprint 1-2

| # | Categoria | Descrizione | File coinvolti |
|---|---|---|---|
| M1 | Bottoni | `btn-xs` non standard; classi `btn` mancanti; btn-outline-info vs secondary | Tutte le liste backoffice |
| M2 | Form | Doppio pattern feedback errori (invalid-feedback vs text-danger small) | group-class-manager, vari |
| M3 | Loading state | Tre pattern diversi (spinner, disabled attr, text swap) senza consistenza | Vari |
| M4 | Badge | Area athlete usa classi badge custom non condivise con backoffice | athlete/* |
| M5 | Inline style | `style="width: Npx"` su filtri rompe responsivita mobile | member-list, exercise-list |
| M6 | Accessibilita | Nessun skip link; contrasto #FF6B00 su dark borderline WCAG AA | athlete.blade.php |

### LOW — Sprint 3+

| # | Categoria | Descrizione | File coinvolti |
|---|---|---|---|
| L1 | Card-outline | Uso non sistematico; nessuna regola chiara | trainer-calendar, training-report |
| L2 | Alpine | `onmouseover` inline da migrare a CSS/Alpine | athlete/exercise-catalog |
| L3 | JS | `fetch()` unread count hard-coded nell'HTML | athlete.blade.php |
| L4 | Skeleton | Nessun placeholder/skeleton durante caricamento tabelle | Tutte le liste |
| L5 | Sorting | Sorting colonne tabelle assente | Tutte le liste |

---

## 10. File Prioritari per Fase 1

```
resources/views/layouts/backoffice.blade.php      — skip link, flash alert
resources/views/layouts/athlete.blade.php         — CSS custom estrazione, media queries
resources/views/livewire/backoffice/calendar/trainer-calendar.blade.php  — modal ARIA
resources/views/livewire/backoffice/access-log-list.blade.php            — modal ARIA
resources/views/livewire/backoffice/admin/feature-flag-manager.blade.php — modal ARIA
resources/views/livewire/backoffice/members/member-list.blade.php        — btn-xs, inline style
resources/views/livewire/backoffice/exercises/exercise-list.blade.php    — btn-xs, inline style
```

---

---

## Fase 1 — Interventi applicati (2026-06-28)

**Commit `b3062bb`** — `feat(ui)`: CSS custom + skip link
- Creato `public/css/backoffice.css` con utilities `filter-w-xs/sm/md/lg`, `table-actions`, `.skip-link`
- Layout `backoffice.blade.php`: skip link "Salta al contenuto" + `#main-content` wrapper

**Commit `d459406`** — `fix(a11y)`: ARIA modali
- `trainer-calendar` (2 modali), `feature-flag-manager`, `booking-list`, `access-log-list`
- Aggiunto: `role="dialog"`, `aria-modal="true"`, `aria-labelledby`, `aria-label="Chiudi"` sui pulsanti ×, `aria-hidden="true"` sull'`&times;`

**Commit `745d2bb`** — `fix(a11y)`: aria-label bottoni icon-only
- `member-list`, `exercise-list`, `mesocycle-list`, `booking-list`, `trainer-calendar`, `group-class-manager`
- Aggiunto `aria-label` contestuale (es. "Modifica Mario Rossi") e `aria-hidden="true"` sulle icone FontAwesome

**Commit `76555d9`** — `fix(ui)`: btn-xs → btn-sm
- 14 file backoffice — replace globale

**Commit `2dd49ff`** — `fix(ui)`: normalizza errori form
- `trainer-calendar` (3 campi), `booking-list` (1), `group-class-manager` (5)
- Pattern unico: `@error('f') is-invalid @enderror` sull'input + `<span class="invalid-feedback">`

**Commit `6a4a87c`** — `fix(ui)`: inline style width → classi CSS
- 8 file — `style="width: Npx"` → `filter-w-xs/sm/md/lg`
- Container filtri: aggiunto `flex-wrap` dove mancante

---

## Fase 2 — Brand identity layer (2026-06-28)

**Commit `2ba073d`** — `feat(brand)`: logo SVG + brand CSS
- `public/images/iron-gym-logo.svg`: dumbbell 32×32, fill `#E85D04`
- `public/css/iron-gym-brand.css`: 170 righe scoped su `body.iron-gym-brand`
  - CSS custom properties: `--ig-primary`, `--ig-primary-dk`, `--ig-sidebar-bg`, `--ig-sidebar-hd`, `--ig-glow`
  - Override sidebar (bg `#1A1A2E`, item attivo arancio, hover rgba)
  - Override navbar (border-bottom 2px arancio)
  - Override `btn-primary`, `btn-outline-primary`, `bg-primary`, `badge-primary`, `text-primary`, `border-primary`
  - Override `card-outline.card-primary`, form focus, paginazione, progress bar
  - Font: `Oswald` su h1-h5/card-title/brand-text, `Inter` su body

**Commit `3a5366b`** — `feat(brand)`: config AdminLTE
- `logo`: `<b>IRON</b>&nbsp;GYM`
- `logo_img`: `images/iron-gym-logo.svg` (rimosso `img-circle elevation-3`)
- `preloader.img.path`: nuovo logo SVG
- `classes_body`: `iron-gym-brand` (attiva layer brand)

**Commit `fecf203`** — `feat(brand)`: layout Google Fonts
- Preconnect `fonts.googleapis.com` + `fonts.gstatic.com`
- `Oswald:wght@400;600;700` + `Inter:wght@400;500;600` via Google Fonts
- Link a `iron-gym-brand.css` dopo `backoffice.css`

---

## Finding aperti post-Fase 2

| Finding | Note |
|---|---|
| H4 — Athlete layout non responsive su tablet | Richiede refactor `athlete.blade.php` + estrazione CSS dedicato |
| M3 — Loading state 3 pattern diversi | Impatto basso, standardizzabile iterazione futura |
| M4 — Badge athlete classi custom | Non critico finché area athlete mantiene dark theme separato |
| L1-L5 — Finding LOW | Opzionali, nessun impatto funzionale |
