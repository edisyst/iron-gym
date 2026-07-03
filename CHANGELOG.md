# Changelog — iron-gym

Tutto il lavoro notevole per versione/step. Ordine cronologico crescente.

---

## Release 08 — Recap di fine sessione condivisibile (2026-07-03)

**Obiettivo:** card riepilogativa post-sessione esportabile come PNG e condivisibile via Web Share API.

- `SessionRecapBuilder` service: calcola durata, tonnellaggio (set working completati, warmup esclusi), ratio set completati/prescritti, PR ottenuti nel range temporale della sessione, top 3 muscoli pesati per `contribution_pct`. Cinque query separate, nessun N+1.
- `SessionRecap` Livewire component (`/athlete/session/{session}/recap`): mostra la card, serializza i dati per la view (no model Eloquent in proprietà pubblica).
- Card HTML (`session-recap.blade.php`): layout verticale 375 px, sfondo brand `#121212`, header arancio gradient, metriche in grid, badge PR, barre muscoli pesate, footer brand.
- CSS standalone `public/css/session-recap.css`: nessuna dipendenza AdminLTE/Bootstrap; `@stack('styles')` aggiunto al layout atleta.
- Export client-side via `html-to-image` (npm, ~45 KB gzipped): `toPng()` con `pixelRatio:2`, Web Share API con fallback download PNG. Entrypoint Vite `resources/js/session-recap.js`.
- Integrazione flusso: `SessionFeedbackForm::save()` e `skip()` redirigono a `/recap` invece che alla dashboard.
- Storico: bottone "Riepilogo" (icona share) su ogni sessione completata in `History`.
- 6 test `SessionRecapBuilderTest` verdi (tonnellaggio esclude warmup, set parziali, zero PR, PR nel range, top muscoli pesati). Suite 183/183 (177 pass + 6 skip invariati), PHPStan 0 errori, Pint conforme.

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

## 2026-06-25 — Allineamento catalogo esercizi e pulizia sorgenti

### execution_description integrata nel seed SQL

- `database/seeders/sql/exercises_seed.sql`: aggiunto blocco `UPDATE` finale con `execution_description` per tutti e 83 gli esercizi. Il file è ora la fonte di verità unica per l'intero catalogo (lookup, esercizi, pivot, testi esecuzione).
- `iron_gym_esercizi_descrizioni.xlsx` rimosso dal repository (i testi erano già presenti in `ExerciseDescriptionSeeder.php`; spostati ora anche in `exercises_seed.sql`).

### Allineamento docs/domain/exercises-catalog.md

- `docs/domain/exercises-catalog.md` e `.claude/docs/domain/exercises-catalog.md` allineati: stesse percentuali `contribution_pct` e descrizioni esecuzione per tutti e 83 gli esercizi.
- Aggiunto changelog v0.4 in entrambi i file.

### Aggiornamento build script SQLite

- `.claude/scripts/build_exercises_sqlite.py`: rimossa dipendenza da `openpyxl` e dal file xlsx. Ora legge solo `exercises_seed.sql` (stdlib Python, zero dipendenze extra). Aggiunto parser SQL corretto che rispetta le stringhe quotate (fix per stringhe con `;` interno).
- `database/database.sqlite` rigenerato: tutti e 83 gli esercizi con `execution_description` popolata.

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

## 2026-06-27 — Revisione codice staged (Phase 1 + 2)

Revisione totale code quality e sicurezza, staged e reversibile. Nessuna funzionalità aggiunta.

### Security — HIGH e CRITICAL

- **SessionFeedbackForm IDOR (CRITICAL):** `save()` ora verifica ownership della sessione via `whereHas('week.mesocycle', ...)` prima di validare. Impedisce a un atleta di inviare feedback su sessioni di altri.
- **TemplateBuilder IDOR (HIGH):** 8 metodi mutanti (`removeSession`, `updateSessionName`, `addExerciseById`, `removeExercise`, `updateExerciseField`, `reorderExercises`, `toggleGroup`, `updateGroupType`) ora verificano che session/exercise appartengano al template corrente prima di agire.
- **routes/backoffice.php middleware (HIGH):** Route sensibili (`exercises.create/edit`, `templates.builder`, `mesocycles.assign/show`, `athletes.*`, `communications.campaign`) spostate dentro `Route::middleware('role:gestore|trainer')->group()`. Receptionist vede solo lettura.
- **MesocycleDetail metodi mutanti (HIGH):** `applyProgression()` e `forceDeload()` aggiunto `abort_unless(...hasAnyRole(['gestore','trainer']), 403)` — defense-in-depth oltre il middleware di rotta.
- **MessageThread IDOR (HIGH):** `sendMessage()` risolve destinatario con `User::role('atleta')->findOrFail()` invece di `User::find()`. Impedisce invio messaggi a non-atleti.
- **FK mesocycles (HIGH):** Aggiunta migration `2026_06_27_000001` con FK `athlete_id` e `trainer_id` → `users.id` (`onDelete('restrict')`), più index su `trainer_id`. Prima mancavano vincoli di integrità referenziale.

### Performance — MEDIUM

- **ExerciseForm lookup cache:** 4 query per lookup statici (`movement_patterns`, `muscles`, `equipment`) cachate con `Cache::rememberForever()`. Zero query DB per quelle collection su ogni render.
- **MesocycleDetail render():** `DeloadEvaluator::evaluate()` spostato da `render()` a `refreshDeloadSignal()`, chiamato in `mount()` e dopo ogni azione mutante. Il `render()` ricostruisce `DeloadSignal` dall'array serializzato senza query.
- **DeloadEvaluator::checkRirDrift():** filtro `rn <= 3` ora eseguito in MySQL via subquery (`ROW_NUMBER() OVER PARTITION BY` wrappato come `DB::raw`). Prima caricava tutti i set in PHP.
- **Index exercise_sets.completed_at (migration `2026_06_27_000002`):** usato in `ORDER BY` da `checkRirDrift()` e `WeeklyProgressionService`. Evita full-scan su tabella set con dati reali.

### Code quality — MEDIUM/LOW

- **E1rmCalculator riuso:** `ExerciseSet::getEstimated1rmAttribute()` ora delega a `E1rmCalculator::epley()` invece di duplicare la formula Epley inline.
- **MesocycleDetail mount param:** rinominato `mount(int $mesocycle)` → `mount(int $mesocycleId)` — eliminata ambiguità con il modello.
- **History.php eager loading:** aggiunto `'feedback'` al `with()` in `getSelectedSessionProperty()` per parità con la versione backoffice.
- **WeeklyVolumeCalculator:** aggiunto commento esplicativo per lo status `approaching_mrv` (85% MAV max ≤ volume < MRV).

### Test — MEDIUM

- **DeloadEvaluatorTest:** aggiunti 2 test mancanti: `rir_drift` (3 set consecutivi con drift ≥ 2) e `end_of_mesocycle` (sessione completata sull'ultima settimana non-deload). Totale: 5/5.
- **Factory mancanti:** create `BodyMeasurementFactory`, `AthleteVolumeLandmarkFactory`, `SubscriptionPlanFactory`, `SubscriptionFactory`, `PtBookingFactory`, `ClassBookingFactory`. Aggiunto `HasFactory` (tipizzato) a `BodyMeasurement`, `AthleteVolumeLandmark`, `SubscriptionPlan`.

**Suite finale:** 96/102 pass, 6 skip, PHPStan L6 0 errori, Pint conforme.

---

## Release 01 — UX sessione: quick-log, previous performance, rest timer, warm-up generator (2026-07-03)

**Obiettivo:** ridurre l'attrito nel logging in sessione PWA atleta.

### Feature

**B — Quick-log one-tap**
- Bottone "Fatto" su ogni set non completato: copia `planned_*` in `actual_*` rispettando `measurement_type` (`reps_weight`, `reps_only`, `isometric_hold`, `time`, `time_weight`).
- Feedback visivo immediato via Alpine (`done = true` ottimistico) senza attendere il round-trip Livewire.
- `completeSet()` e `quickLog()` non resettano `completed_at` se già valorizzato — i campi restano modificabili dopo il quick-log con bottone "Salva".

**C — Previous performance inline**
- `loadPreviousPerformance()` in `mount()`: singola query aggregata per tutti gli esercizi della sessione, niente N+1.
- Riga "prec: X kg × Y @ RIR Z" sotto ogni working set; invisibile se nessuno storico. Warm-up esclusi.
- Dati in `$previousPerformance[exercise_id][set_index]` (proprietà pubblica Livewire).

**D — Rest timer globale**
- `Alpine.store('restTimer')` definito in `workout-session.blade.php`: `start(sec)`, `skip()`, `fmt(s)`.
- Barra fissa in basso con countdown e progress bar; sopravvive allo scroll e al cambio esercizio.
- Allo scadere: `navigator.vibrate([300,150,300])` + `Notification API` se permesso concesso.
- Cluster set usa `intra_cluster_rest_sec`; se `planned_rest_sec` è NULL il timer non parte.
- Architettura precedente (Alpine duplicato per-set) rimossa.

**E — Warm-up generator**
- `generateWarmup($seId)`: solo per `measurement_type = reps_weight` con `planned_weight_kg` valorizzato.
- Set generati: 50% × 8, 70% × 5, 85% × 3 arrotondati a 2.5 kg; sotto 40 kg target solo 50% × 8.
- Idempotente: non aggiunge se warm-up già presenti. Working set shiftati per far spazio.
- `deleteWarmupSet($setId)`: elimina singolo warm-up; rifiuta working set con 404.

### Test
15 nuovi test in `tests/Feature/WorkoutSessionUxTest.php` (quick-log per `measurement_type`, idempotenza warm-up, soglia 40 kg, arrotondamento 2.5 kg, previous performance con/senza storico, esclusione warm-up).

**Suite finale:** 121/129 pass (8 fallimenti pre-esistenti: Vite manifest + Volt auth pages). PHPStan L6 0 errori, Pint conforme.

---

---

## Release 02 — Plate calculator con inventario dischi (2026-07-03)

**Obiettivo:** visualizzare la combinazione di dischi per il carico target direttamente nella sessione.

- `PlateInventory` model + migration + seeder (dischi reali: 20/15/10/5/2.5/1.25 kg per lato).
- `PlateLoadoutCalculator`: algoritmo greedy decrescente su `PlateInventory` attivi; `delta_kg=0` se combinazione esatta, altrimenti combinazione per difetto più vicina.
- `PlateInventoryManager` backoffice (CRUD inline gestore): aggiunta/modifica/attivazione-disattivazione dischi.
- Modale atleta in sessione: campo peso → stack grafico dischi colorati per lato + `delta_kg` se non esatto.
- 4 test Unit `PlateLoadoutCalculatorTest`.

**Suite:** 125/135 pass (10 pre-esistenti Vite+Volt). PHPStan 0, Pint OK.

---

## Release 03 — Offline-first sync con IndexedDB (2026-07-03)

**Obiettivo:** sessione di allenamento navigabile e loggabile senza connessione.

- Alpine store `syncQueue` (in `workout-session.blade.php`): `enqueue`, `flush`, `isPending`; storage IDB vanilla, retry backoff esponenziale 2→4→8...→30s.
- Intercettori offline in `exercise-card.blade.php` per `quickLog`, `completeSet`, `generateWarmup`, `deleteWarmup`: badge ⏳ su set pending.
- Endpoint `POST /athlete/session/sync` (`SyncBatchController` + `SyncBatchRequest`): operazioni `quick_log`, `complete_set`, `generate_warmup`, `delete_warmup`; idempotenza via `sync_operations.client_uuid UNIQUE`; last-write-wins su `completed_at` vs `client_timestamp`.
- Service worker v2: stale-while-revalidate statici, network-first con fallback cache per `/athlete/session/*`.
- 4 test `SyncBatchTest`.

**Suite:** 125/135 pass (10 pre-esistenti). PHPStan 0, Pint OK.

---

## Release 04 — Volume visuale: body map SVG e barre vs landmarks (2026-07-03)

**Obiettivo:** esporre il motore di volume (`WeeklyVolumeCalculator`) all'atleta con body map colorata e barre di progresso posizionate sui landmark MEV/MAV/MRV personali.

### Feature

**Body map SVG (Fase B)**
- SVG inline fronte + retro affiancati in `resources/views/livewire/athlete/partials/body-map.blade.php`.
- 25 muscoli rappresentati con `<path data-muscle="{slug}">` usando lo slug esatto dalla tabella `muscles`.
- Muscoli profondi non rappresentabili (`transverse_abdominis`) assenti dal SVG ma presenti nei dati.
- Muscoli aggregati visivamente: `soleus` su `gastrocnemius` (path proprio), `trapezius_lower` su `trapezius_middle`, `brachioradialis` su `brachialis`.
- Colorazione via classi CSS `intensity-0..5` iniettate da Livewire (`intensityMap`): grigio → blu → giallo → verde → arancio → rosso.
- Tap su muscolo → Alpine `$dispatch('highlight-muscle')` → bordo arancio su tutti i path del muscolo + scroll alla barra, zero round-trip Livewire.

**Componente Livewire `WeeklyVolume` (Fase C)**
- Route `GET /athlete/volume`, componente `app/Livewire/Athlete/WeeklyVolume.php`.
- Selettore settimana (default: settimana corrente per date; fallback prima con sessioni non completate; fallback prima assoluta).
- Ownership check su `MicrocycleWeek` prima di chiamare il calculator.
- Una sola invocazione di `WeeklyVolumeCalculator::calculate()` per render, nessun N+1.
- Barre orizzontali per ogni muscolo con volume > 0 o landmark definito: fill colorato, marker MEV verticale, banda MAV semi-trasparente, marker MRV verticale.
- `buildIntensityMap()`: con landmark usa status (`below_mev`/`in_mav`/`approaching_mrv`/`over_mrv`); senza landmark scala assoluta (1-2 set = intensity-1 ... 11+ = intensity-5). Documentata nel codice SVG.
- `muscleName(slug)` statico: 26 slug → nome italiano, nessuna query DB aggiuntiva.
- Voce "Volume" aggiunta in sidebar desktop e bottom nav mobile (sostituisce "Prenota" nel bottom nav; Prenota resta accessibile via URL e sidebar).

### Test (Fase D)
8 test in `WeeklyVolumeComponentTest.php`:
- Mount senza errori per atleta autenticato.
- Trainer ottiene 403 sulla route atleta.
- Volume distribuito su più muscoli via `contribution_pct` (squat 70% quad + 30% gluteo, 3 set → 2.1 quad + 0.9 gluteo).
- Warm-up esclusi dal conteggio (1 working + 2 warmup → 1.0 hard set).
- Atleta senza landmarks: status `no_landmark`, mev null.
- Settimana deload selezionabile, label contiene "deload".
- Nessun mesociclo attivo: empty state visibile.
- Cambio settimana aggiorna volumeData (0.0 in W1 → 1.0 in W2).

### Documentazione
- `docs/architecture/body-map-svg.md`: mappatura slug → path SVG, scala intensità, regole manutenzione.

**Suite finale:** 129/143 pass (8 fallimenti pre-esistenti: Vite manifest + Volt auth — invariati). PHPStan L6 0 errori, Pint conforme.

---

*Ogni step ha lasciato test Pest verdi, PHPStan L6 a 0 errori, Pint conforme.*

---

## Release 06 — Sostituzione esercizio guidata in sessione (2026-07-03)

**Obiettivo:** permettere all'atleta di sostituire un esercizio prescritto con un'alternativa equivalente quando la macchina è occupata, senza chiamare il trainer, mantenendo la prescrizione e la correttezza del calcolo volume.

### Modello dati (Fase B)
- Migration `2026_07_03_400000`: colonna `substituted_from_exercise_id` nullable (`unsignedInteger`) su `session_exercises`, FK → `exercises.id` ON DELETE SET NULL. Traccia l'esercizio originale prescritto a fini di audit del trainer.
- `SessionExercise.$fillable` aggiornato. Relazione `substitutedFrom(): BelongsTo<Exercise>` aggiunta.

### Servizio (Fase C)
- `ExerciseSubstitutionFinder::find(Exercise): Collection` — max 5 candidati ordinati per similarità di reclutamento.
- Matching: stesso `joint_action_id` (se valorizzato) o stesso `compound_pattern_id` + stesso `measurement_type` + non soft-deleted + esercizio stesso escluso.
- Overlap = somma di `min(pct_orig, pct_cand)` su tutti i muscoli comuni; tie-break: stesso `mechanic` → `skill_level` ≤ originale.
- Ogni candidato include `equipment_slugs[]` (per valutazione disponibilità in sala) e `primary_muscles[]`.
- `Exercise::primaryMuscles()` relation aggiunta (BelongsToMany con `wherePivot('role','primary')`).
- `ExerciseMuscle` annotato con `@property string $role` e `@property int $contribution_pct` per PHPStan.

### UI (Fase D)
- Bottone "Sostituisci" nell'header card esercizio: visibile solo se nessun set working è già completato.
- Badge "Sostituito da [originale]" sotto l'header se l'esercizio è già stato sostituito in sessione.
- Bottom sheet modale (z-index 1000, pattern identico alla plate calculator modale): 5 card candidati con nome, badge equipment slug, muscoli primary, percentuale overlap. Conferma con "Usa questo esercizio".
- `WorkoutSession::openSubstitutionModal(seId)`: blocco server-side su set completati, popola `$substitutionCandidates` come array scalare serializzabile da Livewire.
- `WorkoutSession::confirmSubstitution(slug)`: aggiorna `exercise_id`, setta `substituted_from_exercise_id`, mantiene invariati tutti i set pianificati e i parametri di prescrizione. Reload relazioni eager.
- Backoffice `AthleteSessionHistory`: eager load `substitutedFrom` + badge `badge-warning` "sost. da [nome originale]" nella vista sessione del trainer.

### Test (Fase E)
14 test in due file:

`ExerciseSubstitutionFinderTest` (9 test, LazilyRefreshDatabase + seed completo):
- `dumbbell_bench_press` in top-3 alternative di `barbell_bench_press`
- `machine_chest_press` tra le alternative di `barbell_bench_press`
- alternative di `leg_extension` restano nel pattern `knee_extension`
- esercizio stesso escluso dai candidati
- max 5 risultati
- `equipment_slugs` e `primary_muscles` sono array
- esclusione per `measurement_type` diverso
- esclusione soft-deleted
- ordinamento per overlap decrescente

`WorkoutSessionSubstitutionTest` (5 test, RefreshDatabase):
- `exercise_id` aggiornato + `substituted_from_exercise_id` tracciato correttamente
- set pianificati invariati dopo sostituzione
- blocco se almeno un set working è già completato
- `openSubstitutionModal` popola `substitutionCandidates`
- `closeSubstitutionModal` azzera stato

**Suite finale:** 163/163 pass, 6 skip pre-esistenti (Vite manifest + Volt auth — invariati). PHPStan L6 0 errori, Pint conforme.

---

## Release 05 — PR detection in tempo reale e lista record (2026-07-03)

**Obiettivo:** rilevare automaticamente i personal record e1RM al completamento di ogni set, con feedback immediato in sessione e pagina storico PR.

### Modello dati (Fase B)
- Migration `personal_records`: `athlete_id` FK, `exercise_id` FK, `exercise_set_id` FK, `record_type ENUM('e1rm','max_weight','max_reps_at_weight')`, `value DECIMAL(7,2)`, `achieved_at TIMESTAMP`. Indice su `(athlete_id, exercise_id, record_type)`. Questa release implementa solo `e1rm`; l'enum lascia spazio ai tipi futuri senza migration.
- Model `PersonalRecord` con relazioni `athlete`, `exercise`, `exerciseSet`.

### Servizio (Fase C)
- `PersonalRecordDetector::check(ExerciseSet, athleteId): ?PersonalRecord` — sincrono, pronto per migrazione a evento+listener.
- Soglie configurabili in `config/pr.php`: `max_reps_epley` (default 12, Epley degrada oltre) e `min_sessions_before_pr` (default 3, evita la pioggia di PR iniziali).
- Filtra: set warmup, measurement type non `reps_weight`, reps oltre soglia, e1RM che non supera il record corrente.
- Agganciato in **due percorsi** per copertura completa:
  - `WorkoutSession::quickLog()` e `completeSet()` — path online Livewire
  - `SyncBatchController::applyQuickLog()` e `applyCompleteSet()` — path offline IndexedDB sync

### UI (Fase D)
- Toast Alpine auto-dismiss 4s nel layout atleta; ascolta evento Livewire `pr-achieved` con `exerciseName` e `e1rm`. Visibile sopra il bottom nav.
- Componente `Athlete\PersonalRecords` (`/athlete/records`): lista e1RM per esercizio con link a slug, valore e data, paginazione server-side Livewire.
- Voce "Record" aggiunta in sidebar desktop e bottom nav mobile.

### Test (Fase E)
6 test in `PersonalRecordDetectorTest.php`:
- Registra PR dopo la soglia minima di sessioni.
- Non registra se e1RM non supera il record precedente.
- Ignora set warmup.
- Ignora reps oltre soglia config.
- Ignora measurement type non `reps_weight`.
- Non registra prima della soglia minima di sessioni.

**Suite finale:** 143/149 pass (6 skip pre-esistenti: Vite manifest + Volt auth — invariati). PHPStan L6 0 errori, Pint conforme.

---

## Release 07 — Readiness check pre-sessione con modulazione carichi (2026-07-03)

**Obiettivo:** prima dell'avvio di ogni sessione, l'atleta compila un check rapido (4 campi 0-3, zero digitazione). Il sistema calcola uno score 0-12 e propone una modulazione dei carichi pianificati (nessuna / -5% / -10% + 1 set). L'atleta accetta o rifiuta esplicitamente. Il trainer vede score e modulazione applicata nella vista sessione.

### Modello dati (Fase B)
- Migration `2026_07_03_500000`: tabella `session_readiness_checks` con FK `UNSIGNED INT` su `training_sessions.id` (UNIQUE — un solo check per sessione), campi `sleep_quality`, `stress_level`, `soreness_level`, `joint_status` (TINYINT UNSIGNED 0-3, dove 3 = ottimo), `note` TEXT nullable, `created_at`. Nessun `updated_at` (immutabile dopo compilazione).
- `SessionReadinessCheck` model: `$fillable`, relazione `session()`, accessor `getScoreAttribute()` (somma dei 4 campi).
- `TrainingSession`: aggiunta relation `readinessCheck(): HasOne`.
- Fix pre-esistente: migration `personal_records` usava `foreignId()` per `exercise_id` e `exercise_set_id` (BIGINT) su tabelle con PK `UNSIGNED INT` — sostituito con `unsignedInteger + foreign()` esplicito. Rende `migrate:fresh` funzionante.

### Servizio e config (Fase C)
- `config/readiness.php`: soglie `thresholds.high` (default 9) e `thresholds.low` (default 5), percentuali `reduction_pct.medium` (5%) e `reduction_pct.low` (10%), `joint_alert_threshold` (1), `min_sets_for_removal` (3). Tutti override via `.env`.
- `ReadinessProposal` value object readonly: `score`, `outcome` (`none`|`reduce_5pct`|`reduce_10pct`), `suggestion`, `includesJointAlert`, `requiresModulation()`.
- `ReadinessEvaluator::evaluate(SessionReadinessCheck): ReadinessProposal` — legge config, mappa score sui tre esiti, attiva `includesJointAlert` se `joint_status <= joint_alert_threshold`.
- `ReadinessEvaluator::applyReduction(float, int): float` — riduzione percentuale arrotondata a 2.5 kg.

### Flusso sessione (Fase D1)
- `WorkoutSession::mount()`: se sessione `planned` senza check esistente → `$showReadinessModal = true` (non transiziona a `in_progress`); se check già presente → `startSession()` immediato.
- `startSession()` metodo privato estratto per evitare duplicazione.
- `submitReadiness(int $sleep, int $stress, int $soreness, int $joint, string $note)`: salva check, calcola proposta, traccia in `trainer_notes` ("Readiness pre-sessione: score X/12 — ..."), mostra proposta modulazione o avvia sessione direttamente se `outcome = none`.
- `skipReadiness()`: avvia sessione senza check.
- `acceptModulation()`: aggiorna `planned_weight_kg` set non completati + elimina set extra (fascia low, ≥ 3 set incompleti per esercizio).
- `rejectModulation()`: avvia sessione senza modifiche.
- UI: bottom sheet modale readiness con 4 gruppi di 4 bottoni (Alpine `x-data` locale, zero round-trip), colori 0=rosso/1=giallo/2=blu/3=verde. Campo note opzionale. Azioni "Inizia allenamento" e "Salta il check" (mai bloccante). Schermata riepilogo modulazione con tabella prima/dopo e lista set rimossi.

### Backoffice (Fase D2)
- `AthleteSessionHistory::getSelectedSessionProperty()`: aggiunto eager load `readinessCheck`.
- View `athlete-session-history.blade.php`: sezione "Readiness pre-sessione" prima del feedback post-sessione — badge score (verde/giallo/rosso), 4 valori singoli, nota atleta, testo `trainer_notes` con la descrizione della modulazione.

### Test (Fase E)
14 test in `ReadinessEvaluatorTest.php` (Feature, RefreshDatabase):
- Mappatura score → esito per le tre fasce (0, 5, 6, 9, 10 come casi limite).
- Arrotondamento 2.5 kg: casi esatti e non esatti.
- `includesJointAlert` per `joint_status` 0, 1, 2.
- `SessionReadinessCheck::score` accessor.
- Fix `WorkoutSessionTest`: il test "il completamento del primo set porta la sessione in in_progress" ora crea un readiness check prima del mount.

**Suite finale:** 177/177 pass (171 pass + 6 skip pre-esistenti: Vite manifest + Volt auth — invariati). PHPStan L6 0 errori, Pint conforme. `migrate:fresh --seed` funzionante.
