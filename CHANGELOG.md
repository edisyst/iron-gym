# Changelog — iron-gym

Tutto il lavoro notevole per versione/step. Ordine cronologico crescente.

---

## Step 0 — Discovery e modello di dominio

**Obiettivo:** fondamenta del dominio bodybuilding prima di scrivere una riga di codice applicativo.

- Definito glossario di dominio: Set, Rep, Working set, 1RM, e1RM (Epley/Brzycki/Lombardi), RPE, RIR, Volume (tonnellaggio e hard sets), MEV/MAV/MRV, Mesocycle, Microcycle, Session, Deload, Autoregulation, Tempo.
- Mappate 4 personas: Atleta, Trainer, Gestore, Receptionist.
- Definita tassonomia esercizi su 7 assi: movement pattern (lookup), mechanic, equipment (lookup), plane, laterality, skill level, measurement type.
- Tabella `movement_patterns` con colonna `category` (`compound_pattern` / `joint_action`): 12 compound + 15 joint action = 27 pattern. Sostituisce l'ENUM precedente per supportare crescita del catalogo senza migration.
- Doppia FK nullable `compound_pattern_id` / `joint_action_id` su `exercises` con CHECK XOR (esattamente una valorizzata). Progettata in v0.3 dopo aver separato gli assi tassonomici.
- Schema SQL preliminare completo: `movement_patterns`, `muscles`, `equipment`, `exercises`, `exercise_muscle` (con `role` e `contribution_pct`), `exercise_equipment`, `workout_templates`, `template_sessions`, `template_session_exercises`, `mesocycles`, `microcycle_weeks`, `sessions`, `session_exercise_groups`, `session_exercises`, `exercise_sets`, `session_feedbacks`, `session_exercise_feedbacks`, `athlete_volume_landmarks`.
- Catalogo seed 83 esercizi su 9 gruppi muscolari (petto, schiena, spalle, bicipiti, tricipiti, gambe, polpacci, trapezio, addome). 26 muscoli, 14 equipment.
- Modello di set con prescrizione + esecuzione sullo stesso record (`planned_*` / `actual_*`). Tecniche speciali (drop set, rest-pause, myo-reps, cluster, 21s) modellate via `set_sequence_id` + `sequence_index` + `set_subtype`.
- Regole di progressione: volume settimanale per muscolo calcolato pesando i `contribution_pct`, progressione da MEV a MRV su n settimane, deload automatico all'ultima settimana.
- Feedback post-sessione scala 0-3 su 5 metriche: pump, soreness, perceived effort, joint pain, performance.
- Documenti prodotti: `docs/domain/step-0-discovery.md`, `docs/domain/exercises-catalog.md`, `docs/domain/glossary.md`.

---

## Step 1 — Skeleton + core gestionale minimo

**Obiettivo:** struttura Laravel funzionante con gestionale base tesserati e infrastruttura CI/CD.

- Progetto Laravel 11 con PHP 8.3. Composer, Vite, Node 20 LTS.
- Docker Compose: servizi `app` (PHP-FPM), `db` (MySQL 8), `redis` (Redis 7), `node`.
- GitHub Actions CI: job `test` (Pest), `pint` (code style), `phpstan` (Larastan L6), `deploy-staging` (SSH rsync condizionato a merge su `main`).
- Migration di tutto lo schema training-core da step-0 in ordine di dipendenza FK.
- `ExerciseSeeder` che carica `database/seeders/sql/exercises_seed.sql` via `DB::unprepared()`. Seed 83 esercizi, 26 muscoli, 14 equipment, 27 pattern con INSERT JOIN su slug (no hardcode id).
- `RoleSeeder`: ruoli Spatie `gestore`, `trainer`, `receptionist`, `atleta`.
- `DemoSeeder`: dati fittizi per sviluppo locale (solo `app()->isLocal()`).
- Auth con Laravel Breeze stack Livewire: login, registrazione, verifica email, reset password.
- Middleware alias `role`, `permission`, `role_or_permission` via spatie/laravel-permission.
- Modelli: `User` (HasRoles, MustVerifyEmail), `Member` (anagrafica tesserato), `SubscriptionPlan`, `Subscription`, `AccessLog`.
- Backoffice: `MemberList`, `MemberForm`, `SubscriptionList`, `SubscriptionForm`, `AccessLogList`.
- Layout backoffice (`layouts/backoffice.blade.php`) con AdminLTE 3, flash messages Alpine.js, slot content.
- Route file separati: `routes/web.php`, `routes/backoffice.php` (middleware `auth + role`), `routes/athlete.php`.
- Health check `/health` via Laravel built-in `/up`.

---

## Step 2 — Libreria esercizi e workout builder

**Obiettivo:** catalogo esercizi navigabile e costruttore di template/schede.

- Componenti backoffice: `ExerciseList` (filtri mechanic/pattern/equipment/search, paginazione), `ExerciseDetail` (scheda con muscoli, equipment, pattern), `ExerciseForm` (CRUD con gestione pivot exercise_muscle e exercise_equipment).
- Observer `ExerciseObserver`: flush cache tag `exercises` su create/update/delete.
- `TemplateList`, `TemplateForm`, `TemplateBuilder` (drag-and-drop sessioni ed esercizi, gestione set prescrittivi).
- Modelli: `Exercise` (soft delete, scopes per filtro), `WorkoutTemplate`, `TemplateSession`, `TemplateSessionExercise`, `MovementPattern`, `Muscle`, `Equipment`.
- Cache Redis con tag per la lista esercizi (invalidazione selettiva).
- Validazione CHECK XOR `compound_pattern_id` / `joint_action_id` duplicata a livello `ExerciseForm` (Form Request) oltre al DB constraint.

---

## Step 3 — App atleta v1 e workout logging

**Obiettivo:** PWA per gli atleti con logging sessioni in tempo reale.

- Layout `layouts/athlete.blade.php`: design dark (#121212), bottom navigation a 6 voci, CSS inline ottimizzato per mobile.
- PWA: `manifest.json`, service worker (`public/sw.js`), meta tag apple/android, icone generate da `php artisan pwa:generate-icons`.
- Componenti atleta: `Dashboard` (sessione odierna, prossimi allenamenti), `WorkoutSession` (logging live set con peso/reps/RIR, timer riposo, note), `SessionFeedbackForm` (feedback 0-3 su 5 metriche post-sessione), `History` (storico sessioni completate).
- Instanziamento mesociclo da template: copia struttura in `sessions` / `session_exercises` / `exercise_sets` con dati prescrittivi.
- Observer `TrainingSessionObserver`: aggiorna `status` sessione e `started_at`/`completed_at`.
- Route atleta: `/athlete/dashboard`, `/athlete/session/{id}`, `/athlete/history`, `/athlete/progress`, `/athlete/bookings`, `/athlete/messages`, `/athlete/profile`.
- Model `TrainingSession` con scope per status, relazioni a `SessionExercise` → `ExerciseSet`.

---

## Step 4 — Periodizzazione e autoregolazione

**Obiettivo:** motore di progressione del volume settimanale e rilevamento segnali deload.

- `WeeklyVolumeCalculator`: calcola hard sets per muscolo per settimana pesando `contribution_pct` su soli working set (esclusi `is_warmup`). Restituisce status `below_mev`, `in_mav`, `approaching_mrv`, `over_mrv` per ogni muscolo.
- `WeeklyProgressionService`: per ogni muscolo sotto MRV aggiunge set alla settimana successiva (distribuisce su esercizi esistenti in proporzione). Su deload riduce volume del 50% e carico del 10%.
- `DeloadEvaluator`: aggrega feedback delle ultime 2 settimane per muscolo; trigger deload se joint_pain ≥ 2 per 2 settimane consecutive, MRV raggiunto su muscoli principali, RIR drift, fine mesociclo.
- `VolumeLandmarkManager` (backoffice): CRUD landmark MEV/MAV/MRV per atleta-muscolo con valori default da `config/volume_landmarks.php`.
- `MesocycleDetail`: tabella volume per muscolo con barre di progressione (colore per status), bottoni "Applica progressione" e "Forza deload".
- `MesocycleAssign`: assegna template a un atleta scegliendo data inizio e numero settimane; crea mesociclo + microcycle_weeks (ultima = deload).
- Value objects: `ProgressionResult` (action, note), `DeloadSignal` (isDeloadNeeded, activeTriggers, notes).
- e1RM calcolato da `WeeklyVolumeCalculator` con formula Epley (`w * (1 + r/30)`).

---

## Step 5 — Tracking corporeo e analytics

**Obiettivo:** misurazioni antropometriche, foto progressi, grafici andamento atleta.

- Migration `body_measurements`: peso, altezza, BF%, circonferenze (vita, fianchi, petto, braccio, coscia), note, `measured_at`.
- Migration `progress_photos`: storage path, tipo (front/side/back), data.
- `BodyMeasurementForm` (backoffice e atleta): form misurazioni con storico tabellare.
- `ProgressPhotoUpload` (atleta): upload foto con preview, organizzazione per data.
- `Progress` (atleta): grafici Chart.js per peso e BF% nel tempo, tabella misurazioni recenti.
- `AthleteAnalytics` (backoffice): grafici e1RM per esercizi indicatori, volume settimanale per muscolo nel tempo, confronto IMC/BF%.
- Observer `BodyMeasurementObserver`: aggiorna cache analytics atleta.
- Storage con disco locale, URL firmati per le foto, path `{athlete_id}/photos/{year}/{filename}`.

---

## Step 6 — Prenotazioni e calendario

**Obiettivo:** gestione disponibilità trainer, prenotazioni PT, corsi collettivi.

- Migration `trainer_availability`: slot settimanali ricorrenti (day_of_week, start/end time, max_slots).
- Migration `pt_bookings`: prenotazione singola (atleta, trainer, data/ora, status, note).
- Migration `group_classes`: corso collettivo (nome, trainer, data/ora, max_partecipanti, location).
- Migration `class_bookings`: iscrizione atleta a corso collettivo.
- `TrainerCalendar` (backoffice): vista settimanale con FullCalendar.js, drag-and-drop slots, visualizzazione booking PT e corsi.
- `AvailabilityManager` (backoffice): CRUD ricorrenze settimanali trainer.
- `BookingList` (backoffice): lista prenotazioni con filtri stato/trainer/data, azioni approva/rifiuta/cancella.
- `GroupClassManager` (backoffice): CRUD corsi, lista iscritti.
- `Booking` (atleta): lista slot disponibili, form prenotazione PT, lista corsi con iscrizione.
- Observer `TrainerAvailabilityObserver`: ricalcola slot disponibili su modifica ricorrenza.
- Observer `PtBookingObserver`: invia notifica atleta/trainer su conferma/cancellazione.

---

## Step 7 — CRM, comunicazione e notifiche multicanale

**Obiettivo:** messaggistica interna, campagne di comunicazione, notifiche push PWA e in-app.

- Migration `messages`: thread atleta-trainer con `parent_id` per reply, `read_at`.
- Migration `communication_templates`: template email/SMS/push con variabili `{{nome}}`.
- Migration `communication_logs`: log invii con status e timestamp.
- Migration `notifications` (Laravel standard): notifiche in-app con `read_at`.
- Migration `push_subscriptions`: endpoint VAPID per Web Push.
- `MessageThread` (backoffice): chat real-time Livewire atleta↔trainer, polling ogni 3s.
- `Messages` (atleta): interfaccia chat PWA, badge messaggi non letti nella bottom nav.
- `CommunicationCampaign` (backoffice): selezione segmento (scadenza imminente, no accesso N giorni, compleanno), anteprima, invio batch con job coda.
- `NotificationBell` (backoffice): campanella con contatore notifiche non lette, dropdown.
- Notifiche push via Web Push API (VAPID). Chiavi generate con Node.js, `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` in .env.
- Push subscription: atleta concede permesso → endpoint salvato → worker invia.
- `InactiveMembersCommand`: job schedulato che identifica tesserati inattivi da N giorni e li mette in coda per campagna automatica.
- Scheduler config: `app/Console/Kernel.php` (o `routes/console.php` in L11) con cron `schedule:run`.

---

## Step 8 — Reportistica gestore e finanza

**Obiettivo:** KPI palestra, report fatturato, occupancy trainer, export CSV/PDF.

- `KpiService`: calcola `revenueByPeriod`, `revenueByPlan`, `revenueByTrainer`, `trainerOccupancy`, `newMembersCount`, `retentionRate`, `churnRate` via query raw ottimizzate con indici.
- `ManagerDashboard` (backoffice, solo gestore): info-box KPI periodo selezionabile, grafici Chart.js (fatturato mensile 12 mesi, revenue per piano donut, occupancy trainer barre orizzontali), tabella revenue per trainer, tabella tesserati a rischio churn (scaduti 0-30gg senza rinnovo).
- `FinancialReport` (backoffice, solo gestore): report mensile/trimestrale/annuale con export CSV (`league/csv`) e PDF (via HTML print-friendly).
- `TrainingReport` (backoffice, gestore+trainer): sessioni completate, volume medio per atleta, aderenza alle schede.
- Cache KPI con tag Redis `kpi`, TTL 1h, invalidazione su pagamento abbonamento.
- `KpiSummaryCommand`: genera e invia per email il report KPI mensile automatico (schedulato ogni 1° del mese).

---

## Step 9 — Hardening, DevOps e deployment

**Obiettivo:** produzione-ready: backup automatici, health check, PWA icons, Telescope, ottimizzazioni.

- `spatie/laravel-backup`: backup automatico giornaliero DB + storage. Disco `local` in dev, `s3` in prod via `BACKUP_DISK`. Retention 7G → 4W → 3M. Mail alert su fallimento a `BACKUP_NOTIFY_EMAIL`.
- Health check `/health` (`/up`): risponde 200 se app + DB + Redis OK, 503 altrimenti.
- `GeneratePwaIcons` artisan command: genera icone PWA da `resources/images/icon.png` (192px, 512px, maskable).
- Laravel Telescope: installato come dev dependency, accessibile su `/telescope`. Abilitato via `TELESCOPE_ENABLED=true`.
- `config/backup.php` personalizzato con lista esclusioni (`.git`, `node_modules`, `vendor`, log, cache).
- CI GitHub Actions aggiornato: job `deploy-staging` esegue `php artisan config:cache`, `route:cache`, `view:cache`, `migrate --force`, `queue:restart` dopo deploy SSH.
- Rate limiting su route auth (60 req/min), backoff su failed login.
- Content Security Policy base via middleware.
- Ottimizzazione query N+1 con `with()` su relazioni critiche (mesociclo con settimane, sessioni con esercizi).

---

## Step 10 — Pilota in palestra reale

**Obiettivo:** strumenti per roll-out controllato: feature flags, error tracking, feedback in-app, onboarding reale.

### Feature flags — `laravel/pennant`

- Package `laravel/pennant` installato. Tabella `features` migrata.
- 4 flag definiti in `AppServiceProvider::defineFeatureFlags()`:
  - `periodization_engine`: gestore o email in lista `FEATURE_BETA_TRAINERS`.
  - `push_notifications`: atleti e trainer.
  - `group_classes`: booleano globale da `FEATURE_GROUP_CLASSES` (default false).
  - `financial_reports`: solo gestore.
- Blade directive `@feature('flag') ... @endfeature` registrata globalmente.
- Gate `view-group-classes` per nascondere "Corsi collettivi" dalla sidebar AdminLTE.
- `MesocycleDetail`: bottone "Applica progressione" gated su `periodization_engine`.
- Layout atleta: script registrazione service worker push gated su `push_notifications`.
- `ManagerDashboard`: intera sezione report finanziari gated su `financial_reports`.
- `FeatureFlagManager` (`/backoffice/admin/feature-flags`, solo gestore): tabella flag con stato globale, toggle con modale di conferma, `Feature::activateForEveryone()` / `Feature::deactivateForEveryone()`.
- `config/features.php`: `FEATURE_BETA_TRAINERS` (lista CSV email), `FEATURE_GROUP_CLASSES` (bool).

### Error tracking — `spatie/laravel-flare`

- Package `spatie/laravel-flare` v3 installato. Config pubblicata.
- `FLARE_KEY` in `.env.example` (vuoto in dev, valorizzato in prod).
- `censor.client_ips: true` → IP anonimizzati nei report.
- Context utente (id, email, ruoli) aggiunto via `Flare::context()` in AppServiceProvider.
- `AuthorizationException`, `ValidationException`, `ModelNotFoundException` aggiunte a `dontReport()` in `bootstrap/app.php` → non segnalate a Flare.

### Feedback in-app

- Migration `feedback_submissions`: `user_id` (nullable FK), `page_url`, `type` ENUM (`bug/suggestion/confused`), `body`, `user_agent`, `internal_notes`.
- Model `FeedbackSubmission` con relazione `user()`.
- `InAppFeedback` (Livewire, `Shared`): widget flottante bottom-right su tutti i layout (backoffice e atleta). Form con tipo, testo max 500 chars, page URL auto-popolato via JS. Submit → crea record + invia mail a `FEEDBACK_EMAIL` via `Mail::raw()`.
- `FeedbackList` (`/backoffice/admin/feedback`, solo gestore): tabella con filtri tipo/data, note interne inline salvabili senza ricaricare.
- `config/services.php`: chiave `feedback_email` da env `FEEDBACK_EMAIL`.

### Onboarding pilota

- `config/pilot.php`: 4 piani abbonamento reali (Mensile 50€, Trimestrale 130€, Annuale 450€, 10 ingressi 80€) + credenziali gestore da env.
- `PilotSeeder`: `firstOrCreate` per piani e account gestore (idempotente).
- `PilotInitCommand` (`php artisan pilot:init`): esegue `PilotSeeder` con `$this->confirm()` interattiva prima di procedere.
- `DatabaseSeeder` già separato: `RoleSeeder` + `ExerciseSeeder` sempre, `DemoSeeder` solo in local.

### Checklist e test

- `docs/devops/go-live-checklist.md`: checklist markdown compilabile (ambiente, DB, servizi, accessi, comunicazioni, roll-out graduale).
- `tests/Feature/FlareTest.php`: verifica dispatch job + ignored exceptions.
- `tests/Feature/SmokeTest.php`: 6 test sola-lettura (backoffice 200, dashboard atleta 200, health 200, 83 esercizi, 27 pattern, 4 ruoli). Skip automatico su SQLite in-memory; attivi su staging MySQL reale.

---

## 2026-06-22 — Storico atleta e navigazione backoffice

### Fix bug nomi colonne feedback in TrainingReport

- `TrainingReport::loadDrilldown()`: corretti nomi colonna da `energy_level`, `motivation_level`, `joint_pain_level`, `overall_rating`, `notes` ai nomi reali dello schema: `pump`, `soreness_prev`, `perceived_effort`, `joint_pain`, `performance`, `note`.
- `training-report.blade.php`: aggiornato il drilldown con le cinque label corrette e badge colorati (0 → grigio, 1 → verde, 2 → giallo, 3 → rosso).

### Fix bug MesocycleDetail::forceDeload()

- `forceDeload()` usava `$this->lastProgressionResult` (proprietà inesistente) invece di `$this->lastProgressionResultData` (array serializzabile). Corretto allineandosi al pattern di `applyProgression()`.

### Pagina profilo atleta nel backoffice

- Nuovo componente `Backoffice/Athletes/AthleteProfile`: contenitore con tab Alpine.js (Storico allenamenti, Analytics, Misurazioni, Volume landmarks, Messaggi).
- Route `GET /backoffice/athletes/{athleteId}/profile` con middleware `role:gestore|trainer`.
- Header con avatar iniziali, nome, email, ruolo, mesociclo attivo + settimana corrente.

### Storico sessioni atleta (backoffice)

- Nuovo componente `Backoffice/Athletes/AthleteSessionHistory`: versione backoffice dello storico, filtrabile per mesociclo.
- Tabella con colonne Data, Sessione, Mesociclo, Trainer, Set (completati/totali), Durata, Feedback.
- Pannello dettaglio inline con esercizi, set planned → actual, e1RM calcolato, badge feedback per tutti e 5 i campi.
- Sicurezza: `showDetail()` carica solo sessioni dell'atleta specificato via `whereHas('week.mesocycle', ...)`.

### Fix view @extends → componenti Livewire embeddabili

- `athlete-analytics.blade.php` e `body-measurement-form.blade.php`: convertiti da `@extends('adminlte::page')` a wrapper `<div>`. Le view con @extends causavano l'override del layout quando embedded via @livewire in AthleteProfile.

### Navigazione

- `MemberList`: link "Profilo allenamento" verso `backoffice.athletes.profile` per tesserati con account.
- `MesocycleList`: nuova colonna azioni con link "Profilo atleta" e "Dettaglio mesociclo".
- `MesocycleDetail`: link "Vedi profilo completo atleta" nell'header.
- `config/adminlte.php`: voce "Report allenamento" nella sidebar sezione TRAINING.

### Test

- `tests/Feature/AthleteHistoryTest.php`: 4 test (trainer vede atleta suo, trainer non vede sessioni atleta altrui — 200 con dati filtrati, atleta bloccato 403, gestore vede tutti).

---

## 2026-06-22 — Verifica e fix stile

- Verificato end-to-end: tutti i task A-F già implementati e funzionanti.
- `tests/Feature/AthleteHistoryTest.php`: 4/4 test verdi.
- Suite completa: 90/96 test verdi, 6 skipped, 0 failure.
- PHPStan L6: 0 errori.
- Fix stile Pint in `AppServiceProvider.php`, `WeeklyProgressionService.php`, `tests/Feature/TrainingFlowTest.php`.

---

*Ogni step ha lasciato test Pest verdi, PHPStan L6 a 0 errori, Pint conforme.*
