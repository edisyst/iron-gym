# HK01 Audit Report v2 — Sezioni 1 e 3

Branch: `develop`
Data: 2026-08-22
Strumenti: PHPStan 2.2.2 + Larastan 3.10.0 (livello 6), analisi manuale via grep
Nota: `shipmonk/dead-code-detector` installato (v1.3.3) ma non eseguibile su Windows (output vuoto, exit 1 senza stderr). Analisi sezione 1 svolta manualmente.

**Sezioni validate e immutate:** C (Dipendenze Composer) e D (Documentazione) — non toccate.

---

## Premessa: errori nel report v1

Il report originale (hk01-report.md) conteneva tre voci fabricate nella sezione E:

| Voce originale | Problema |
|---|---|
| `app/Models/DumbbellInventory.php` — `scopeActive()` riga 42 | Il metodo non esiste. Il file è 38 righe e non contiene scope. |
| `app/Models/PtBooking.php` — `scopeActive()` riga 82 | Il metodo non esiste. `PtBooking` ha un solo metodo pubblico: `canBeCancelledFree()`. |
| `app/Models/PtBooking.php` — `scopeForDate()` riga 93 | Il metodo non esiste. Il file termina a riga 86. |

Queste voci sono state scartate perché non verificabili (i file aperti non contengono i metodi citati).

---

## Sezione 1 — Codice morto

### PHPStan livello 6

**Esito:** 0 errori. Il codebase supera l'analisi statica senza segnalazioni.

### Analisi manuale — scope Eloquent

Verificati tutti gli scope nei model con grep sul codebase (`app/`, `resources/`, `routes/`, `tests/`).

| Model | Scope | Utilizzato? | Evidenza |
|---|---|---|---|
| `Message` | `scopeUnread` | SÌ | `Messages.php:148`, `MessageThread.php:52` |
| `Message` | `scopeConversation` | SÌ | `Messages.php:56,140`, `MessageThread.php:23,52,60` (chiamata statica `Message::conversation(...)`) |
| `MovementPattern` | `scopeCompoundPatterns` | SÌ | `ExerciseForm.php:304` |
| `MovementPattern` | `scopeJointActions` | SÌ | `ExerciseForm.php:305` |
| `OpeningHour` | `scopeRecurring` | SÌ | `OpeningHoursManager.php:78,136,149,170` |
| `OpeningHour` | `scopeOverrides` | SÌ | `OpeningHoursManager.php:82,215,228,248` |
| `PlateInventory` | `scopeActive` | SÌ | `PlateLoadoutCalculator.php:34` |
| `Subscription` | `scopeActive` | SÌ | `AccessLogList.php:76`, `SubscriptionList.php:28` |
| `Subscription` | `scopeExpiringSoon` | SÌ | `SubscriptionList.php` |
| `TrainerAvailability` | `scopeRecurring` | SÌ | `AvailabilityManager.php:59` |
| `TrainerAvailability` | `scopeOverrides` | SÌ | `AvailabilityManager.php:66` |
| `TrainerAvailability` | `scopeForDate` | SÌ | `TrainerCalendar.php` |

**Risultato: nessuno scope inutilizzato.**

### Analisi manuale — voci ereditate dal report v1

| Voce | Stato attuale | Note |
|---|---|---|
| `Dashboard.sessionStatusClass()` | RIMOSSA | HK01 già applicato; metodo assente in `app/Livewire/Athlete/Dashboard.php` |
| `Dashboard.sessionStatusLabel()` | RIMOSSA | HK01 già applicato; metodo assente |
| `config/barbell.php` | RIMOSSA | HK01 già applicato; file non esiste |

### Analisi manuale — Jobs, Notifications, Console Commands

| Categoria | Elemento | Stato |
|---|---|---|
| Job | `ExportFinancialReportCsv` | Usato — `FinancialReport.php:26` |
| Job | `ExportMembersListCsv` | Usato — `FinancialReport.php:33` |
| Job | `SendCampaignMessages` | Usato — `CommunicationCampaign.php:62` |
| Job | `NotifyWaitlistPromotion` | Usato — `ClassBookingService.php:98` |
| Job | `SendMedicalCertExpiryReminders` | Schedulato — `routes/console.php` |
| Job | `SendSubscriptionExpiryReminders` | Schedulato — `routes/console.php` |
| Job | `SendSessionReminders` | Schedulato — `routes/console.php` |
| Job | `HealthCheckJob` | Schedulato — `routes/console.php` |
| Command | `GeneratePwaIcons` | Strumento artisan manuale (`pwa:generate-icons`) — documentato in CLAUDE.md |
| Command | `PilotInitCommand` | Strumento artisan manuale (`pilot:init`) — documentato in CLAUDE.md |
| Command | `KpiSummaryCommand` | Strumento artisan manuale (`reports:kpi-summary`) — non schedulato |
| Command | `InactiveMembersCommand` | Strumento artisan manuale (`reports:inactive-members`) — non schedulato |

I command `KpiSummaryCommand` e `InactiveMembersCommand` non sono schedulati né chiamati da codice. Sono strumenti di reportistica invocabili manualmente. Non classificabili come dead code, ma non documentati in CLAUDE.md né in CHANGELOG.

### Riepilogo Sezione 1

| Classificazione | Voce |
|---|---|
| CERTO (già rimosso da HK01) | `Dashboard.sessionStatusClass/Label`, `config/barbell.php` |
| FABRICATO nel v1 | `DumbbellInventory.scopeActive`, `PtBooking.scopeActive`, `PtBooking.scopeForDate` |
| NUOVI FINDING | Nessuno |

**Nessun nuovo dead code trovato nel codebase corrente.**

---

## Sezione 3 — Componenti orfani

### Metodo di verifica

Per ogni componente Livewire si verifica che:
1. Esiste un file PHP in `app/Livewire/`
2. È montato da una route in `routes/athlete.php` o `routes/backoffice.php`, oppure è embedded via `@livewire(...)` o `<livewire:...>` in una view Blade

### Voci ereditate dal report v1

| Voce | Stato attuale |
|---|---|
| `app/Livewire/Athlete/Progress.php` | RIMOSSA da HK01 — file non esiste |
| `resources/views/livewire/athlete/progress.blade.php` | RIMOSSA da HK01 — file non esiste |
| `resources/views/dashboard.blade.php` | RIMOSSA da HK01 — file non esiste |

### Verifica componenti esistenti

**Athlete — tutti i componenti montati da route:**

| Componente | Route |
|---|---|
| `Dashboard` | `GET /athlete/` |
| `WorkoutSession` | `GET /athlete/session/{session}` |
| `SessionRecap` | `GET /athlete/session/{session}/recap` |
| `TrainingHub` | `GET /athlete/history` |
| `BodyMeasurementForm` | `GET /athlete/measurements` |
| `ProgressPhotoUpload` | `GET /athlete/photos/upload` |
| `ExerciseCatalog` | `GET /athlete/exercises` |
| `ExerciseDetail` | `GET /athlete/exercises/{exercise:slug}` |
| `Booking` | `GET /athlete/bookings` |
| `WeeklyVolume` | `GET /athlete/volume` |
| `PersonalRecords` | `GET /athlete/records` |
| `Profile` | `GET /athlete/profile` |
| `Messages` | `GET /athlete/messages` |
| `SessionFeedbackForm` | Embedded — `workout-session.blade.php:805` via `<livewire:athlete.session-feedback-form ...>` |

**Backoffice — tutti i componenti montati da route o embedded:**

Tutti i 35 componenti backoffice hanno route corrispondente, con due eccezioni embedded:
- `NotificationBell` — embedded in `layouts/backoffice.blade.php:18` via `@livewire('backoffice.shared.notification-bell')`
- `AthleteSessionHistory` — embedded in `livewire/backoffice/athletes/athlete-profile.blade.php:66` via `@livewire('backoffice.athletes.athlete-session-history', ...)`

### Nuovo finding — Partial blade orfano

| Path | Tipo | Classificazione | Evidenza |
|---|---|---|---|
| `resources/views/livewire/athlete/partials/exercise-card.blade.php` | Blade partial | **CERTO** | Grep `exercise-card` su tutti i file `*.blade.php` → zero risultati. Il file non è incluso da nessuna view. Era il partial per il layout multi-esercizio pre-UX02; sostituito da `partials/session-exercise.blade.php` in UX02 ma non rimosso. Il file esiste, contiene ~250 righe di markup, ed è referenziato solo nel suo stesso header (`Partial per una card esercizio...`). |

### Riepilogo Sezione 3

| Classificazione | Voce |
|---|---|
| CERTO (già rimosso da HK01) | `Athlete\Progress.php`, `progress.blade.php`, `dashboard.blade.php` |
| CERTO (nuovo finding) | `partials/exercise-card.blade.php` — partial orfano |
| CONFERMATI IN USO | Tutti gli altri 49 componenti e partial |

---

## Appendice — Nota sul dead-code-detector

`shipmonk/dead-code-detector` v1.3.3 è installato ma non incluso in `phpstan.neon`. Il tentativo di esecuzione con config dedicata ha prodotto output vuoto (exit 1, stderr vuoto) su Windows con Git Bash e PowerShell. Non è stato possibile usarlo come fonte. L'analisi manuale sopra copre le categorie a maggiore rischio (scope Eloquent, Jobs, Notifications, Commands).

Per eseguirlo su Linux/Mac:
```bash
# Aggiungere in phpstan.neon:
includes:
    - vendor/shipmonk/dead-code-detector/rules.neon
# Poi:
php vendor/bin/phpstan analyse --memory-limit=512M --error-format=json > storage/hk01/deadcode.json
# Richiede due passaggi (la prima esecuzione costruisce la cache)
```
