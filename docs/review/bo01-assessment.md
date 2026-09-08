# BO01 — Assessment Uniformità Strutturale UI Backoffice

**Data:** 2026-09-08  
**Scope:** READ-ONLY — nessuna modifica applicata  
**Autore:** assessment automatico (Claude Code)

> **ERRATA (2026-09-08)** — Le sezioni 3.1, 3.9 e 3.10 contenevano un errore sistematico: il fork di assessment leggeva `->layout('layouts.backoffice')` su una riga e classificava il componente come "no page_title" senza leggere `->layoutData([...])` sulla riga successiva (method chain multi-riga). La conseguenza è che 18 view con new-style chained erano state erroneamente classificate come "senza titolo". Le tre sezioni sono state corrette sulla base dei dati effettivi ottenuti via grep. Le sezioni 2.x individuali mantengono i valori originali (errati per il punto 9 di quelle 18 view) e sono da considerare superati dalla correzione in 3.9.
>
> **Nota per Fase 2:** il punto 9 (layout `render()`) di ogni view va riletto dal codice sorgente (`app/Livewire/Backoffice/`), mai dall'assessment. I valori nelle sezioni 2.x sono inaffidabili per quel punto.

---

## 0. Legenda

| Simbolo | Significato |
|---|---|
| ✅ | Conforme al pattern maggioritario |
| ⚠️ | Divergente (lieve) |
| ❌ | Divergente (significativo) |
| — | Non applicabile per tipo view |
| ? | Non determinabile senza esecuzione runtime |

**Tipi view:** `lista` `form` `dettaglio` `dashboard` `calendario` `wizard` `settings` `standalone`

---

## Fase 1 — Inventario view backoffice

**Totale:** 43 file Livewire sotto `resources/views/livewire/backoffice/` + 1 blade standalone + 2 sub-componenti condivisi (esclusi dall'analisi strutturale).

### 1.1 Sub-componenti condivisi (esclusi da Fase 2–3)

| File | Tipo |
|---|---|
| `shared/notification-bell.blade.php` | Widget layout (embeddato in `layouts/backoffice.blade.php`) |
| `settings/manual-viewer.blade.php` | Sub-componente embeddato in `settings-hub` |

### 1.2 Full-page views (43 − 2 condivisi = 41 Livewire + 1 standalone)

| # | Path view (relativo a `livewire/backoffice/`) | Componente PHP | Route (prefisso `/backoffice`) | Ruoli | Tipo |
|---|---|---|---|---|---|
| 1 | `dashboard.blade.php` | `Backoffice\Dashboard` | `/` | gestore | dashboard |
| 2 | `access/access-log-list.blade.php` | `Backoffice\Access\AccessLogList` | `/access-log` | gestore,trainer,receptionist | lista |
| 3 | `access/quick-checkin.blade.php` | `Backoffice\Access\QuickCheckin` | `/checkin` | gestore,trainer,receptionist | speciale |
| 4 | `admin/feedback-list.blade.php` | `Backoffice\Admin\FeedbackList` | `/admin/feedback` | gestore | lista |
| 5 | `admin/plate-inventory-manager.blade.php` | `Backoffice\Admin\PlateInventoryManager` | `/admin/plate-inventory` | gestore | speciale |
| 6 | `admin/feature-flag-manager.blade.php` | `Backoffice\Admin\FeatureFlagManager` | `/admin/feature-flags` | gestore | **deprecata** — redirect a `/settings` |
| 7 | `athletes/athlete-analytics.blade.php` | `Backoffice\Athletes\AthleteAnalytics` | `/athletes/{member}/analytics` | gestore,trainer | dettaglio |
| 8 | `athletes/athlete-profile.blade.php` | `Backoffice\Athletes\AthleteProfile` | `/athletes/{member}` | gestore,trainer,receptionist | dettaglio |
| 9 | `athletes/athlete-session-history.blade.php` | `Backoffice\Athletes\AthleteSessionHistory` | sub-componente (`@livewire`) | — | lista (sub) |
| 10 | `athletes/body-measurement-form.blade.php` | `Backoffice\Athletes\BodyMeasurementForm` | `/athletes/{member}/body-measurements` | gestore,trainer | form+lista |
| 11 | `calendar/availability-manager.blade.php` | `Backoffice\Calendar\AvailabilityManager` | `/availability` | gestore,trainer | speciale |
| 12 | `calendar/booking-list.blade.php` | `Backoffice\Calendar\BookingList` | `/bookings` | gestore,trainer,receptionist | lista |
| 13 | `calendar/class-schedule-manager.blade.php` | `Backoffice\Calendar\ClassScheduleManager` | `/schedule` | gestore | lista+azioni |
| 14 | `calendar/group-class-catalog.blade.php` | `Backoffice\Calendar\GroupClassCatalog` | `/group-classes/catalog` | gestore | lista |
| 15 | `calendar/group-class-manager.blade.php` | `Backoffice\Calendar\GroupClassManager` | `/group-classes` | gestore | lista |
| 16 | `calendar/trainer-calendar.blade.php` | `Backoffice\Calendar\TrainerCalendar` | `/calendar` | gestore,trainer | calendario |
| 17 | `communications/communication-campaign.blade.php` | `Backoffice\Communications\CommunicationCampaign` | `/communication` | gestore | speciale |
| 18 | `exercises/exercise-detail.blade.php` | `Backoffice\Exercises\ExerciseDetail` | `/exercises/{slug}` | gestore,trainer | dettaglio |
| 19 | `exercises/exercise-form.blade.php` | `Backoffice\Exercises\ExerciseForm` | `/exercises/create`, `/exercises/{slug}/edit` | gestore | form |
| 20 | `exercises/exercise-list.blade.php` | `Backoffice\Exercises\ExerciseList` | `/exercises` | gestore,trainer,receptionist | lista |
| 21 | `members/expiry-dashboard.blade.php` | `Backoffice\Members\ExpiryDashboard` | `/reports/expiry` | gestore,receptionist | dashboard |
| 22 | `members/member-form.blade.php` | `Backoffice\Members\MemberForm` | `/members/create`, `/members/{member}/edit` | gestore,receptionist | form |
| 23 | `members/member-list.blade.php` | `Backoffice\Members\MemberList` | `/members` | gestore,trainer,receptionist | lista |
| 24 | `mesocycles/mesocycle-assign.blade.php` | `Backoffice\Mesocycles\MesocycleAssign` | `/mesocycles/assign` | gestore,trainer | wizard |
| 25 | `mesocycles/mesocycle-detail.blade.php` | `Backoffice\Mesocycles\MesocycleDetail` | `/mesocycles/{mesocycle}` | gestore,trainer | dettaglio |
| 26 | `mesocycles/mesocycle-list.blade.php` | `Backoffice\Mesocycles\MesocycleList` | `/mesocycles` | gestore,trainer | lista |
| 27 | `mesocycles/volume-landmark-manager.blade.php` | `Backoffice\Mesocycles\VolumeLandmarkManager` | `/admin/volume-landmarks` | gestore | speciale |
| 28 | `messages/message-thread.blade.php` | `Backoffice\Messages\MessageThread` | `/athletes/{member}/messages` | gestore,trainer | speciale |
| 29 | `reports/financial-report.blade.php` | `Backoffice\Reports\FinancialReport` | `/reports/financial` | gestore | dashboard |
| 30 | `reports/manager-dashboard.blade.php` | `Backoffice\Reports\ManagerDashboard` | `/reports/manager` | gestore | dashboard |
| 31 | `reports/training-report.blade.php` | `Backoffice\Reports\TrainingReport` | `/reports/training` | gestore,trainer | lista+drill |
| 32 | `search/global-search.blade.php` | `Backoffice\Search\GlobalSearch` | `/search` | gestore,trainer,receptionist | speciale |
| 33 | `settings/artisan-runner.blade.php` | `Backoffice\Settings\ArtisanRunner` | `/settings/artisan` | gestore | settings |
| 34 | `settings/feature-flag-manager.blade.php` | `Backoffice\Settings\FeatureFlagManager` | `/settings/feature-flags` (+ embeddato in `settings-hub`) | gestore | settings |
| 35 | `settings/opening-hours-manager.blade.php` | `Backoffice\Settings\OpeningHoursManager` | `/settings/opening-hours` | gestore | settings |
| 36 | `settings/settings-hub.blade.php` | `Backoffice\Settings\SettingsHub` | `/settings` | gestore | settings |
| 37 | `subscriptions/subscription-form.blade.php` | `Backoffice\Subscriptions\SubscriptionForm` | `/subscriptions/create`, `/subscriptions/{sub}/edit` | gestore,receptionist | form |
| 38 | `subscriptions/subscription-list.blade.php` | `Backoffice\Subscriptions\SubscriptionList` | `/subscriptions` | gestore,receptionist | lista |
| 39 | `templates/template-builder.blade.php` | `Backoffice\Templates\TemplateBuilder` | `/templates/{template}/build` | gestore,trainer | speciale |
| 40 | `templates/template-form.blade.php` | `Backoffice\Templates\TemplateForm` | `/templates/create`, `/templates/{template}/edit` | gestore,trainer | form |
| 41 | `templates/template-list.blade.php` | `Backoffice\Templates\TemplateList` | `/templates` | gestore,trainer | lista |
| 42 | *(non Livewire)* `backoffice/settings/api-docs.blade.php` | Blade standalone (controller) | `/settings/api-docs` | gestore | standalone |

---

## Fase 2 — Anatomia strutturale per view

I 9 punti analizzati per ciascuna view:

1. **Titolo** — passaggio `page_title` al layout
2. **Breadcrumb** — presenza `@section('breadcrumb')`
3. **Azioni primarie** — posizione bottoni CTA principali
4. **Filter box** — struttura del riquadro filtri
5. **Body container** — struttura del contenitore dati principale
6. **Empty state** — markup usato per "nessun risultato"
7. **Paginazione** — posizione e condizione di rendering
8. **Spacing** — margine filter→body e padding interno card
9. **Layout `render()`** — stile chiamata al layout

### 2.1 access/access-log-list

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato (`->layout('layouts.backoffice')` senza `page_title`) |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-tools` nella card body (nessun bottone di creazione — view sola lettura) |
| Filter box | ✅ `card card-outline card-primary mb-3` + `card-header` con label "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + `table table-sm table-striped` |
| Empty state | ✅ `<tr><td colspan="4" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `card-footer` **sempre** renderizzato (no `hasPages()`) |
| Spacing | `mb-3` su filter card, nessun gap extra |
| Layout render() | ❌ `->layout('layouts.backoffice')` — stile vecchio, no `page_title` |

### 2.2 access/quick-checkin

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-tools` nella card destra (bottone check-in) |
| Filter box | ❌ Assente — input ricerca inline nella card sinistra |
| Body container | ❌ Layout a due colonne (`row col-md-6`) — non segue pattern card singola |
| Empty state | ⚠️ `<p class="text-muted p-3 mb-0">` (no `<tr><td>`) |
| Paginazione | — Non paginata |
| Spacing | Standard Bootstrap row |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.3 admin/feedback-list

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato (`->layout('layouts.backoffice', ['page_title' => ...])` — verifica: summary dice "FeedbackList" nella lista old-style; confermato) |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ❌ Nessuna |
| Filter box | ⚠️ `card card-outline card-primary mb-3` ma **senza** `card-header` con label "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + tabella |
| Empty state | ✅ `<tr><td colspan="6" class="text-center text-muted py-3">` (py-3 invece di py-4) |
| Paginazione | ⚠️ `card-footer` condizionale con `hasPages()` |
| Spacing | Standard |
| Layout render() | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style con titolo |

### 2.4 admin/plate-inventory-manager

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ `card-tools` con testo inline (no bottone con icona) |
| Filter box | ❌ Assente — due card di sezione senza filter card separata |
| Body container | ❌ `card card-outline card-primary mb-4` + `p-0` — usa `card-outline card-primary` sul body, non sul filtro |
| Empty state | ✅ `<tr><td colspan="5" class="text-center text-muted py-4">` con hint comando artisan |
| Paginazione | ⚠️ `hasPages()` condizionale, per sezione |
| Spacing | `mb-4` tra sezioni |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.5 admin/feature-flag-manager (DEPRECATA)

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Redirect; view residuale senza azioni proprie |
| Filter box | — N/A |
| Body container | ✅ `card` + `card-body p-0` |
| Empty state | — N/A |
| Paginazione | — N/A |
| Layout render() | ✅ Old style con titolo |

> Nota: componente deprecato, redirect a `/settings/feature-flags`. Non considerato nei calcoli Fase 3.

### 2.6 athletes/athlete-analytics

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Nessuna CTA primaria (view analitica) |
| Filter box | ❌ Assente |
| Body container | ❌ `row` di card + card full-width — layout multi-card |
| Empty state | ⚠️ `<p class="text-muted text-center py-3">` inline nelle card grafici |
| Paginazione | — Non paginata |
| Spacing | Standard Bootstrap |
| Layout render() | ✅ Old style con titolo |

### 2.7 athletes/athlete-profile

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ `ml-auto table-actions` nella card-header (non `card-tools`) |
| Filter box | ⚠️ `card card-outline card-primary mb-3` usato come **header bar** — non filtri |
| Body container | ❌ Nav tabs (`ul.nav-tabs`) + sub-componenti `@livewire` — no tabella diretta |
| Empty state | — Delegato ai sub-componenti |
| Paginazione | — Delegata ai sub-componenti |
| Spacing | `mb-3` su header card |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.8 athletes/athlete-session-history (sub-componente)

| Punto | Valore |
|---|---|
| Titolo | — Sub-componente; nessun titolo proprio |
| Breadcrumb | — N/A |
| Azioni primarie | ❌ Assente |
| Filter box | ⚠️ `card card-outline card-secondary mb-3` — usa `card-secondary` invece di `card-primary` |
| Body container | ✅ `card` + `card-body p-0` + tabella |
| Empty state | ✅ `<tr><td colspan="8" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `hasPages()` condizionale |
| Spacing | Standard |
| Layout render() | — Sub-componente |

### 2.9 athletes/body-measurement-form

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-footer` con bottone submit |
| Filter box | ❌ Assente |
| Body container | ❌ Layout `row` due colonne: form card + history card |
| Empty state | ⚠️ `<tr><td colspan="4" class="text-center text-muted">` — manca `py-4` |
| Paginazione | — Non paginata |
| Spacing | Standard Bootstrap row |
| Layout render() | ✅ Old style con titolo |

### 2.10 calendar/availability-manager

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-tools` in ciascuna card |
| Filter box | ❌ Assente — due card di contenuto (non filtri) |
| Body container | ⚠️ `card card-outline card-success` + `card-body p-0` — usa `card-success` invece di `card-primary` |
| Empty state | ✅ `<tr><td colspan="N" class="text-center text-muted py-3">` |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ✅ New style con titolo |

### 2.11 calendar/booking-list

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ❌ Nessun bottone di creazione nella card body |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + `table-responsive` |
| Empty state | ✅ `<tr><td colspan="7" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `hasPages()` condizionale |
| Spacing | Standard |
| Layout render() | ✅ New style con titolo |

### 2.12 calendar/class-schedule-manager

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` con bottone primario nella card |
| Filter box | ❌ Assente — filtri inline in `card-tools` della card unica |
| Body container | ⚠️ `card card-outline card-warning` — usa `card-warning` |
| Empty state | ✅ `<tr><td colspan="7" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `hasPages()` condizionale |
| Spacing | Standard |
| Layout render() | ✅ New style con titolo |

### 2.13 calendar/group-class-catalog

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ❌ Bottone **fuori da ogni card** — in un `d-flex justify-content-between mb-3` con `<h4>` |
| Filter box | ❌ `d-flex justify-content-between mb-3` con `<h4>` — non una card |
| Body container | ✅ `card` + `card-body p-0` |
| Empty state | ✅ `<tr><td colspan="7" class="text-center text-muted py-4">` |
| Paginazione | — Non paginata |
| Spacing | `mb-3` su row header |
| Layout render() | ✅ New style con titolo |

### 2.14 calendar/group-class-manager

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` con bottoni |
| Filter box | ⚠️ Filtri inline in `card-tools` della card body — nessuna filter card separata |
| Body container | ⚠️ `card card-outline card-warning` — usa `card-warning` |
| Empty state | ✅ `<tr><td colspan="6" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `hasPages()` condizionale |
| Spacing | Standard |
| Layout render() | ✅ New style con titolo |

### 2.15 calendar/trainer-calendar

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-tools` con nav (prev/oggi/next) — non bottoni CTA |
| Filter box | ⚠️ Navigazione periodo inline in `card-tools` — nessuna filter card |
| Body container | ⚠️ `card card-outline card-primary` + `card-body p-2` (padding non-zero) |
| Empty state | — FullCalendar gestisce lo stato vuoto internamente |
| Paginazione | — Non paginata |
| Spacing | `p-2` nel card-body |
| Layout render() | ✅ New style con titolo |

### 2.16 communications/communication-campaign

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | In sub-card body (bottone invio) |
| Filter box | ❌ Assente — card di sezione annidate |
| Body container | ❌ `card card-outline card-primary` con due sub-card interne |
| Empty state | — Non applicabile (form) |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.17 dashboard

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Nessuna |
| Filter box | ❌ Assente |
| Body container | ❌ `row` di `small-box` + widget cards — non lista/tabella |
| Empty state | ⚠️ Card condizionale (`@if`) — non markup strutturato |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.18 exercises/exercise-detail

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ❌ `mb-3 d-flex justify-content-end` **fuori da ogni card** — in cima alla view |
| Filter box | ❌ Assente |
| Body container | ❌ Layout multi-card `row` (info, media, muscoli, equipment) |
| Empty state | ⚠️ `<tr><td colspan="3" class="text-center text-muted py-3">` nella tabella muscoli |
| Paginazione | — Non paginata |
| Spacing | `mb-3` su row azioni |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.19 exercises/exercise-form

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ❌ `d-flex` **dopo** l'ultima card — nessuna posizione standard |
| Filter box | — N/A (form) |
| Body container | ❌ Multi-card (info, media, pattern, classificazione, equipment, muscoli) — nessuna card unica |
| Empty state | ⚠️ Alert errore in cima (non empty state di lista) |
| Paginazione | — Non paginata |
| Spacing | Standard tra card |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.20 exercises/exercise-list

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` nella card body |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + tabella |
| Empty state | ✅ `<tr><td colspan="6" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `card-footer` **sempre** renderizzato (no `hasPages()`) |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.21 members/expiry-dashboard

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Nessuna CTA |
| Filter box | ⚠️ `card card-outline card-warning` + `card-header` "Filtri" — usa `card-warning` invece di `card-primary` |
| Body container | ❌ Due card di sezione (certificati + abbonamenti) — non lista unica |
| Empty state | ⚠️ `<p class="text-muted p-3 mb-0">` (non `<tr><td>`) |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.22 members/member-form

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-footer` con bottone submit |
| Filter box | — N/A (form) |
| Body container | ✅ Singola `card` + `card-footer` |
| Empty state | — N/A (form) |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.23 members/member-list

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` (export + nuovo) nella card body |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + tabella |
| Empty state | ✅ `<tr><td colspan="6" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `card-footer` **sempre** renderizzato (no `hasPages()`) |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.24 mesocycles/mesocycle-assign

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | Nei bottoni step wizard interni alla `card-body` |
| Filter box | — N/A (wizard) |
| Body container | Singola `card` con step wizard |
| Empty state | — N/A |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ✅ Old style con titolo |

### 2.25 mesocycles/mesocycle-detail

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ `card-header d-flex` con bottoni azione — non `card-tools` |
| Filter box | ⚠️ Selettore settimana inline in `card-header` — non filter card separata |
| Body container | Singola `card` con layout interno |
| Empty state | ⚠️ `<div class="p-3 text-muted">` (non `<tr><td>`) |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ✅ Old style con titolo |

### 2.26 mesocycles/mesocycle-list

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` nella card body |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + `table-responsive` |
| Empty state | ✅ `<tr><td colspan="8" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `hasPages()` condizionale |
| Spacing | Standard |
| Layout render() | ✅ Old style con titolo |

### 2.27 mesocycles/volume-landmark-manager

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ `card-header d-flex` con bottoni save/reset — non `card-tools` |
| Filter box | ❌ Assente |
| Body container | ✅ `card` + `card-body p-0` con tabelle multiple per gruppo muscolare |
| Empty state | — Nessun empty state (dati sempre presenti se atleta selezionato) |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ✅ Old style con titolo |

### 2.28 messages/message-thread

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Nessuna CTA esterna (input reply in `card-footer`) |
| Filter box | ❌ Assente |
| Body container | ⚠️ `card card-primary card-outline` + `card-body` con `overflow-y:auto` fixed height |
| Empty state | ⚠️ `<p class="text-muted text-center mt-4">` (no `<tr><td>`) |
| Paginazione | — Scroll infinito, no paginazione |
| Spacing | Fixed height card |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.29 reports/financial-report

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato (`render()` senza `page_title`) |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ Nella filter bar (bottoni export), non in `card-tools` separato |
| Filter box | ⚠️ `card card-outline card-primary` con `card-body py-2` — **senza** `card-header` "Filtri" |
| Body container | ❌ KPI row (`info-box`) + chart cards + data cards — layout composito |
| Empty state | ⚠️ `<tr><td colspan="N" class="text-center text-muted">` — manca `py-4` |
| Paginazione | — Non paginata |
| Spacing | `py-2` nel filter card-body |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.30 reports/manager-dashboard

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ Nella filter bar (bottoni export/date), non in `card-tools` |
| Filter box | ⚠️ `card card-outline card-primary mb-3` con `card-body py-2` — **senza** `card-header` "Filtri" |
| Body container | ❌ `row` info-box + chart cards + data tables — layout composito |
| Empty state | ⚠️ `<tr><td colspan="N" class="text-center text-muted">` — manca `py-4` |
| Paginazione | — Non paginata |
| Spacing | `py-2` nel filter card-body |
| Layout render() | ✅ New style con titolo |

### 2.31 reports/training-report

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ⚠️ In `card-tools` del drilldown card (non della lista principale) |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + tabella principale |
| Empty state | ⚠️ `<tr><td colspan="6" class="text-center text-muted">` — manca `py-4` |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.32 search/global-search

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Solo ricerca |
| Filter box | ❌ `row mb-3` con form inline — non una card |
| Body container | ❌ `row` di `card card-outline` per categoria — no tabella unica |
| Empty state | ⚠️ `<p class="text-muted p-3 mb-0">` per sezione (no `<tr><td>`) |
| Paginazione | — Non paginata |
| Spacing | `mb-3` su row ricerca |
| Layout render() | ✅ Old style con titolo |

### 2.33 settings/artisan-runner

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | Bottone "Esegui" per riga nella tabella |
| Filter box | ❌ Assente |
| Body container | ❌ Multiple `card mb-3` per gruppo comando |
| Empty state | — N/A (dati statici da config) |
| Paginazione | — Non paginata |
| Spacing | `mb-3` tra card gruppi |
| Layout render() | ✅ Old style con titolo |

### 2.34 settings/feature-flag-manager

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | Toggle per riga |
| Filter box | ❌ Assente |
| Body container | ❌ Multiple `card mb-3` per gruppo flag |
| Empty state | — N/A (dati statici da Pennant) |
| Paginazione | — Non paginata |
| Spacing | `mb-3` tra card gruppi |
| Layout render() | ✅ Old style con titolo |

### 2.35 settings/opening-hours-manager

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` — new style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-tools` in ciascuna card |
| Filter box | ❌ Assente — due card di contenuto |
| Body container | ⚠️ `card card-outline card-success` + `card card-outline card-warning` — colori non-primary |
| Empty state | ✅ `<tr><td colspan="N" class="text-center text-muted py-3">` |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ✅ New style con titolo |

### 2.36 settings/settings-hub

| Punto | Valore |
|---|---|
| Titolo | ✅ `->layout('layouts.backoffice', ['page_title' => '...'])` — old style |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Navigazione tab |
| Filter box | ❌ Assente |
| Body container | ❌ Nav tabs (`ul.nav-tabs`) + sub-componenti `@livewire` |
| Empty state | — Delegato ai sub-componenti |
| Paginazione | — Non paginata |
| Spacing | `mb-3` sotto nav tabs |
| Layout render() | ✅ Old style con titolo |

### 2.37 subscriptions/subscription-form

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | `card-footer` con bottone submit |
| Filter box | — N/A (form) |
| Body container | ✅ Singola `card` + `card-footer` |
| Empty state | — N/A (form) |
| Paginazione | — Non paginata |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.38 subscriptions/subscription-list

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` (export + nuovo) nella card body |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + tabella |
| Empty state | ✅ `<tr><td colspan="7" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `card-footer` **sempre** renderizzato (no `hasPages()`) |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.39 templates/template-builder

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | — Tab nav in cima, nessuna CTA primaria distinta |
| Filter box | ❌ Assente |
| Body container | ❌ `row` con session cards + sidebar — layout builder custom |
| Empty state | — Nessun empty state visibile (builder sempre attivo) |
| Paginazione | — Non paginata |
| Spacing | Layout builder specifico |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.40 templates/template-form

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | In `card-body` in fondo al form (bottone salva) |
| Filter box | — N/A (form) |
| Body container | `card` singola con `max-width: 680px` — vincolo larghezza non standard |
| Empty state | ⚠️ Alert errori in cima (non empty state) |
| Paginazione | — Non paginata |
| Spacing | `max-width: 680px` — deviazione da layout full-width |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.41 templates/template-list

| Punto | Valore |
|---|---|
| Titolo | ❌ Non passato |
| Breadcrumb | ❌ Assente |
| Azioni primarie | ✅ `card-tools` nella card body |
| Filter box | ✅ `card card-outline card-primary` + `card-header` "Filtri" |
| Body container | ✅ `card` + `card-body p-0` + tabella |
| Empty state | ✅ `<tr><td colspan="8" class="text-center text-muted py-4">` |
| Paginazione | ⚠️ `card-footer` **sempre** renderizzato (no `hasPages()`) |
| Spacing | Standard |
| Layout render() | ❌ `->layout('layouts.backoffice')` — no `page_title` |

### 2.42 backoffice/settings/api-docs.blade.php (STANDALONE)

| Punto | Valore |
|---|---|
| Titolo | — Titolo HTML proprio (`<title>Iron Gym — API Reference</title>`) |
| Breadcrumb | — N/A |
| Azioni primarie | — Scarica YAML + link back — in `ig-topbar` custom |
| Filter box | — N/A |
| Body container | `#swagger-ui` — Swagger UI bundle |
| Empty state | — Gestito da Swagger UI |
| Paginazione | — N/A |
| Spacing | Custom CSS `ig-topbar` |
| Layout render() | — Non usa `->layout()` — blade esteso standalone con `<!DOCTYPE html>` |

---

## Fase 3 — Matrice conformità

Analisi su **40 view full-page Livewire** (escluse: feature-flag-manager deprecata, 2 sub-componenti condivisi, api-docs standalone).

### 3.1 Punto 1 — Titolo (`page_title`)

> ⚠️ **Sezione corretta** — vedi errata in cima. I dati originali erano sbagliati.

**Pattern maggioritario:** `page_title` passato in 38/40 view (95%).

| Variante | View | Count |
|---|---|---|
| New style `->layout()->layoutData(['page_title' => '...'])` | access-log-list, quick-checkin, athlete-profile, availability-manager, booking-list, class-schedule-manager, group-class-catalog, group-class-manager, trainer-calendar, communication-campaign, dashboard, exercise-detail, exercise-form, exercise-list, member-form, member-list, message-thread, financial-report, manager-dashboard, training-report, opening-hours-manager, subscription-form, subscription-list, template-builder, template-form, template-list | **26** |
| Old style `->layout(..., ['page_title' => '...'])` | athlete-analytics, body-measurement-form, feedback-list, mesocycle-assign, mesocycle-detail, mesocycle-list, volume-landmark-manager, global-search, settings-hub, artisan-runner | **10** |
| Old style + deprecata | feature-flag-manager (deprecata, esclusa dai conteggi 3.x) | 1 |
| **Nessun `page_title`** | **plate-inventory-manager, expiry-dashboard** | **2** |

**Divergenze critiche:** solo 2 view senza titolo — mostrano "Iron Gym" (post-BO01) come fallback.

**Inconsistenza stili:** old-style (10) vs new-style (26) — la maggioranza usa già il pattern corretto. Le 10 old-style sono candidate alla migrazione in BO02.

### 3.2 Punto 2 — Breadcrumb

**Pattern maggioritario:** ASSENTE in tutte le 40 view (100%)

Nessuna view imposta `@section('breadcrumb')`. Il layout `layouts/backoffice.blade.php` non riceve dati breadcrumb da nessun componente. Comportamento: AdminLTE mostra solo il content-header senza filo di Arianna.

**Non è una divergenza — è un gap di feature uniforme.**

### 3.3 Punto 3 — Azioni primarie (posizione)

**Pattern maggioritario:** `card-tools` nella card body header (liste) | `card-footer` (form)

| Posizione | View | Tipo view |
|---|---|---|
| `card-tools` in body card | access-log-list, class-schedule-manager, exercise-list, group-class-manager, member-list, mesocycle-list, subscription-list, template-list, trainer-calendar, quick-checkin | lista/speciale |
| `card-footer` con submit | body-measurement-form, member-form, subscription-form | form |
| `card-header d-flex` con bottoni | mesocycle-detail, volume-landmark-manager | dettaglio |
| **Fuori da qualsiasi card** | exercise-detail (top-right `d-flex`), group-class-catalog (accanto a `<h4>`) | ❌ divergente |
| In filter bar | financial-report, manager-dashboard | ⚠️ divergente |
| Nessuna CTA | availability-manager, booking-list, dashboard, athlete-analytics, athlete-profile, communication-campaign, dashboard, expiry-dashboard, global-search, message-thread, training-report, settings/* | — |

**View più divergenti:** `exercise-detail` e `group-class-catalog` (CTA fuori da card); `mesocycle-detail` e `volume-landmark-manager` (card-header d-flex invece di card-tools).

### 3.4 Punto 4 — Filter box

**Pattern maggioritario** (per le view lista che hanno filtri): `card card-outline card-primary` + `card-header` con label "Filtri"

| Variante | View |
|---|---|
| ✅ Standard (card-primary + header "Filtri") | access-log-list, booking-list, exercise-list, member-list, mesocycle-list, subscription-list, template-list, training-report |
| ⚠️ card-primary ma senza header "Filtri" | feedback-list, financial-report, manager-dashboard |
| ⚠️ card-warning (non card-primary) | expiry-dashboard |
| ❌ Nessuna card (d-flex / inline) | group-class-catalog (d-flex esterno), group-class-manager (inline in card-tools), trainer-calendar (nav in card-tools), global-search (row con form) |
| — Assente (giustificato) | dashboard, form views, dettaglio views, settings views, calendar views, message-thread, template-builder |

**View più divergenti:** `group-class-catalog` (pattern totalmente diverso — heading + button fuori card), `financial-report`/`manager-dashboard` (card senza intestazione "Filtri").

### 3.5 Punto 5 — Body container

**Pattern maggioritario** (per liste): `card` + `card-body p-0` + `table table-sm table-striped`

| Variante | View |
|---|---|
| ✅ Standard | access-log-list, booking-list, class-schedule-manager, exercise-list, member-list, mesocycle-list, subscription-list, template-list, feedback-list, athlete-session-history, artisan-runner |
| ⚠️ `card-outline card-warning` invece di neutro | class-schedule-manager, group-class-manager |
| ⚠️ `card-outline card-success` | availability-manager, opening-hours-manager |
| ⚠️ `card-body p-2` (non p-0) | trainer-calendar |
| ❌ Multi-card `row` | dashboard, athlete-analytics, financial-report, manager-dashboard |
| ❌ Nav tabs + @livewire | athlete-profile, settings-hub |
| ❌ Builder custom | template-builder |
| ❌ Multi-card sezionato | plate-inventory-manager, communication-campaign, settings/feature-flag-manager, artisan-runner (multi-card per gruppo) |
| ❌ `max-width: 680px` | template-form |

**Nota:** l'uso di `card-outline card-warning`/`card-success` sul body container (non sul filter box) è un pattern non standard che implica enfasi visiva su sezioni operative — non errato ma non documentato come convenzione.

### 3.6 Punto 6 — Empty state

**Pattern maggioritario** (per liste con tabella): `<tr><td colspan="N" class="text-center text-muted py-4">`

| Variante | View | Note |
|---|---|---|
| ✅ `py-4` standard | access-log-list, exercise-list, group-class-catalog, group-class-manager, member-list, mesocycle-list, subscription-list, template-list, class-schedule-manager, booking-list | colspan variabile 4–8 |
| ⚠️ `py-3` (non py-4) | athlete-session-history, availability-manager, opening-hours-manager, feedback-list, exercise-detail (tabella muscoli), training-report | colspan variabile |
| ⚠️ Manca `py-*` | body-measurement-form, financial-report, manager-dashboard | |
| ❌ `<p class="text-muted p-3 mb-0">` (non `<tr><td>`) | quick-checkin, expiry-dashboard, global-search | |
| ❌ `<p class="text-muted text-center mt-4">` | message-thread | |
| ❌ `<div class="p-3 text-muted">` | mesocycle-detail | |
| ❌ `<p class="text-muted text-center py-3">` | athlete-analytics | |

**Varianti extra-tabella giustificate** per view non-lista (message-thread, global-search, mesocycle-detail). Il problema principale è la difformità `py-3` vs `py-4` nelle view tabella.

### 3.7 Punto 7 — Paginazione

**Pattern maggioritario** (per liste paginate): `card-footer` condizionale con `$items->hasPages()`

| Variante | View |
|---|---|
| ✅ `hasPages()` condizionale | athlete-session-history, booking-list, class-schedule-manager, group-class-manager, mesocycle-list, plate-inventory-manager |
| ❌ `card-footer` SEMPRE (no `hasPages()`) | access-log-list, exercise-list, member-list, subscription-list, template-list |
| — Non paginata | tutti gli altri |

**Problema:** 5 view renderizzano il `card-footer` con `$items->links()` anche quando i risultati stanno in una pagina — produce un `<nav>` vuoto/stub nel DOM che occupa spazio visivo inutilmente.

### 3.8 Punto 8 — Spacing filter→body

**Pattern maggioritario:** `mb-3` sul filter card, nessun gap extra prima del body card

| Variante | View |
|---|---|
| ✅ `mb-3` standard | access-log-list, booking-list, exercise-list, member-list, mesocycle-list, subscription-list, template-list, training-report, feedback-list |
| ⚠️ `mb-4` | plate-inventory-manager (tra sezioni) |
| ⚠️ `py-2` nel card-body filter (altezza ridotta) | financial-report, manager-dashboard |
| ❌ Nessuna filter card → nessun gap codificato | group-class-catalog, group-class-manager, global-search |

### 3.9 Punto 9 — Layout `render()`

> ⚠️ **Sezione corretta** — vedi errata in cima. I dati originali erano sbagliati.

**Pattern da adottare come standard:** `->layout('layouts.backoffice')->layoutData(['page_title' => '...'])` (new style, Livewire 3 nativo)

| Variante | View | Count |
|---|---|---|
| New style + titolo (`->layoutData`) | access-log-list, quick-checkin, athlete-profile, availability-manager, booking-list, class-schedule-manager, group-class-catalog, group-class-manager, trainer-calendar, communication-campaign, dashboard, exercise-detail, exercise-form, exercise-list, member-form, member-list, message-thread, financial-report, manager-dashboard, training-report, opening-hours-manager, subscription-form, subscription-list, template-builder, template-form, template-list | **26** |
| Old style + titolo (`->layout(..., [...])`) | athlete-analytics, body-measurement-form, feedback-list, mesocycle-assign, mesocycle-detail, mesocycle-list, volume-landmark-manager, global-search, settings-hub, artisan-runner | **10** |
| Nessun titolo (qualsiasi stile) | plate-inventory-manager, expiry-dashboard | **2** |

**Lavoro residuo per BO02 (punto 9):** 10 view da convertire da old-style a new-style; 2 view a cui aggiungere il titolo mancante. Nessuna view da zero titolo tranne queste 2.

### 3.10 Ranking divergenze (view più problematiche)

> ⚠️ **Sezione corretta** — la colonna "no page_title" è stata rimossa dove il page_title era in realtà presente (vedi errata). Score ridotti di conseguenza.

| Rank | View | Problemi principali (escluso render style) | Score |
|---|---|---|---|
| 1 | `group-class-catalog` | CTA fuori card, nessuna filter card standard, heading esterno | 3 |
| 2 | `financial-report` | CTA in filter bar, filter senza header "Filtri", empty state senza `py-4` | 3 |
| 3 | `exercise-detail` | CTA fuori card, multi-card body | 2 |
| 4 | `plate-inventory-manager` | **No `page_title`** (unico punto 9 reale), card-primary su body (invertito), multi-sezione | 3 |
| 5 | `expiry-dashboard` | **No `page_title`** (unico punto 9 reale), filter card-warning, empty state `<p>` non-tabella | 3 |
| 6 | `exercise-form` | CTA fuori card, multi-card body | 2 |
| 7 | `manager-dashboard` | CTA in filter bar, filter senza header "Filtri", empty state senza `py-4` | 3 |
| 8 | `athlete-profile` | card-primary usata come header non filtro, ML-auto invece di card-tools | 2 |
| 9 | `mesocycle-detail` | card-header d-flex invece di card-tools, empty state non-tabella | 2 |
| 10 | `template-form` | max-width 680px non standard | 1 |

Nota: il render() old-style (10 view) non è contato come divergenza strutturale nel ranking perché non impatta il layout visivo — è una questione di stile PHP da uniformare in BO02.

---

## Fase 4 — Impatto sui test

### 4.1 Test con `assertSee` / `assertDontSee` su markup backoffice

Tutti i test usando `assertSee` verificano **testo contenuto**, non classi CSS o struttura HTML. Il rischio di rottura in un refactoring strutturale (spostamento di elementi, cambio classi card) è limitato.

| Test file | View/componente | Assert | Rischio refactoring |
|---|---|---|---|
| `BackofficeDashboardExpiryWidgetTest` | `dashboard` | `assertSee('Scadenze imminenti')` | Basso — testo statico |
| `ClassScheduleManagerTest` | `class-schedule-manager` | `assertSee($groupClass->name)` | Basso — dato dinamico |
| `ExerciseDetailPageTest` | `exercise-detail` | `assertSee($exercise->name_it)`, `assertSee('Primario')` | Basso — testo + label |
| `MesocycleOwnershipTest` | `mesocycle-list` | `assertSee/assertDontSee` nomi mesociclo | Basso |
| `MemberNotesTest` | `athlete-profile` | `assertSee('fa-sticky-note', false)` | **Medio** — asserisce class CSS icona |
| `ReceptionistCheckinTest` | `access-log-list` o `member-list` | `assertSee/assertDontSee(route(...))` | Basso — URL route |
| `QuickCheckinTest` | `quick-checkin` | `assertSee('Accesso registrato per')` | Basso — flash message |
| `SubscriptionRenewalTest` | `subscription-list` | `assertSee('Rinnova')` | Basso — label bottone |
| `SubscriptionSuspensionTest` | `subscription-list` | `assertSee('Sospeso')` | Basso — status badge |
| `WeeklyVolumeComponentTest` | `mesocycle-detail` o componente volume | `assertSee('Nessun mesociclo attivo')` | Basso — testo empty state |

**Test sub-componenti AthleteProfile (embedded `@livewire`):**

| Test file | Tab/componente | Assert | Rischio |
|---|---|---|---|
| `AthleteProfileClassBookingsTest` | Tab Corsi | `assertSee('Corsi')`, `assertSee('Confermato')`, `assertSee('Nessun corso prenotato')` | Basso |
| `AthleteProfileAccessLogsTest` | Tab Accessi | `assertSee('Accessi')`, `assertSee('Entrata')`, `assertSee('Nessun accesso registrato')` | Basso |
| `AthleteProfilePtBookingTest` | Tab PT | `assertSee('Confermata')`, `assertSee('Nessuna sessione PT in programma')` | Basso |
| `AthleteProfileSubscriptionTest` | Tab Abbonamenti | `assertSee('Mensile')`, `assertSee('Attivo')`, `assertSee('Nessun abbonamento attivo')` | Basso |
| `AthleteDashboardPtBookingTest` | Dashboard atleta | `assertSee('Prossime sessioni PT')` | Basso — fuori scope backoffice |

### 4.2 Nessun `assertSeeHtml` trovato

Nessun test usa `assertSeeHtml`. Non ci sono test che verificano classi CSS, struttura card o markup HTML specifico — ad eccezione di `MemberNotesTest` che asserisce la stringa `'fa-sticky-note'` con secondo parametro `false` (richiede match esatto, non HTML-escaped).

### 4.3 Rischio per refactoring strutturale

Refactoring delle classi card (`card-outline`, `card-primary`, `card-body p-0`) e delle posizioni dei bottoni **non romperà nessun test esistente** tranne `MemberNotesTest` se il nome dell'icona Font Awesome viene modificato.

Gli empty state text (`'Nessun accesso registrato'`, `'Nessun corso prenotato'`, ecc.) sono testati come stringhe: un cambio di testo romperebbe i test, ma non un cambio di classe CSS o struttura HTML dell'empty state.

---

## Fase 5 — Inventario CSS

### 5.1 `public/css/backoffice.css` — classi strutturali

| Classe | Valore | Uso |
|---|---|---|
| `.filter-w-xs` | `width: 140px` | Input filtro stretto (date, select breve) |
| `.filter-w-sm` | `width: 180px` | Input filtro medio-stretto |
| `.filter-w-md` | `width: 220px` | Input filtro standard |
| `.filter-w-lg` | `width: 300px` | Input filtro largo (testo ricerca) |
| `.table-actions` | `white-space: nowrap` | Colonna azioni tabella — impedisce wrapping bottoni |
| `.skip-link` | Accessibility skip navigation | Solo nel layout |
| `.ig-session-card` | Layout sessione | Solo in `template-builder` |
| `.ig-exercise-list` | Layout lista esercizi | Solo in `template-builder` |
| `.ig-exercise-row` | Riga esercizio | Solo in `template-builder` |
| `.ig-grouped` | Gruppo superset | Solo in `template-builder` |

**Utilizzo reale delle classi filter-w-*:** verificare a runtime — non è garantito che tutte le view le usino, alcune usano `form-control-sm d-inline-block w-auto` inline (come `manager-dashboard`).

### 5.2 `public/css/iron-gym-brand.css` — componenti con impatto strutturale

Il file è un **brand layer puro** scoped a `body.iron-gym-brand`. Non contiene layout override. Elementi rilevanti:

| Selettore | Effetto strutturale |
|---|---|
| `.content-header h1` | Font Oswald — impatta il `page_title` renderizzato nell'`<h1>` del layout |
| `.card-outline.card-primary` | Top border arancio 3px — enfatizza visivamente la filter card |
| `pagination (.page-link, .page-item.active)` | Colore arancio su paginazione — nessun impatto strutturale |

Non ci sono classi di layout (flex, grid, width, margin) nel brand layer. Rimuovere o disattivare `iron-gym-brand` non altera la struttura HTML.

### 5.3 Classi AdminLTE non documentate in backoffice.css ma usate nelle view

| Classe | View che la usano | Note |
|---|---|---|
| `card-tools` | Liste con azioni primarie | Pattern AdminLTE standard per bottoni in card-header |
| `table-responsive` | booking-list, mesocycle-list | Wrapping scroll orizzontale |
| `info-box`, `info-box-icon`, `info-box-content` | dashboard, manager-dashboard | Widget KPI AdminLTE |
| `small-box` | dashboard | Widget statistiche AdminLTE |
| `nav-tabs` | athlete-profile, settings-hub | Tab navigation Bootstrap |

---

## Appendice — Riepilogo numerico

| Metrica | Valore |
|---|---|
| View Livewire full-page backoffice | 41 |
| Sub-componenti condivisi esclusi | 2 (notification-bell, manual-viewer) |
| View standalone (non Livewire) | 1 (api-docs) |
| View con `page_title` passato (qualsiasi stile) | 19 / 40 (47%) |
| View senza `page_title` (mostra "Dashboard" nell'h1) | 20 / 40 (50%) |
| View con breadcrumb | 0 / 40 (0%) |
| View con filter box standard (card-primary + "Filtri") | 8 (solo liste) |
| View con paginazione sempre-on (no `hasPages()`) | 5 |
| View con paginazione condizionale `hasPages()` | 6 |
| Varianti stile render() | 3 (old style titolo / new style titolo / no titolo) |
| Varianti empty state `<tr><td>` | 3 (`py-4` / `py-3` / nessun `py`) |
| Varianti empty state non-tabella | 4 (`<p p-3 mb-0>` / `<p text-center mt-4>` / `<div p-3>` / `<p text-center py-3>`) |
| Test con `assertSee` su markup backoffice | ~28 (spread su 15 file test) |
| Test con `assertSeeHtml` | 0 |
| Test ad alto rischio refactoring strutturale | 1 (`MemberNotesTest` — assert su classe icona CSS) |
| Classi strutturali in backoffice.css | 10 |
| Classi strutturali in iron-gym-brand.css | 0 (brand layer puro) |

### Top 5 difformità per impatto visivo

1. **`page_title` mancante in 20 view** — tutte mostrano "Dashboard" nell'h1 content header
2. **Paginazione senza `hasPages()`** in 5 liste — `card-footer` stub sempre visibile
3. **Filter box fuori card** in `group-class-catalog` — pattern HTML completamente diverso
4. **Empty state `py-3` vs `py-4`** — 6 view usano padding ridotto nelle righe tabella vuote
5. **CTA fuori da ogni card** in `exercise-detail` e `group-class-catalog` — azioni primarie non aganciate alla card body

---

*Assessment completato. Nessuna modifica applicata. In attesa di approvazione esplicita prima di procedere.*
