# iron-gym

Gestionale palestra bodybuilding/fitness. Copre: anagrafica tesserati, abbonamenti, accessi, libreria esercizi, schede allenamento (template e mesocicli), logging sessioni atleta, periodizzazione con volume landmarks, tracking corporeo, prenotazioni PT/corsi, messaggistica trainer-atleta, notifiche automatiche, reportistica gestore, feature flags.

## Stack tecnico

- **Backend:** PHP 8.3, Laravel 11.x
- **Frontend backoffice:** Livewire 3 + Alpine.js, tema AdminLTE 3.x + brand layer Iron Gym
- **App atleta:** stesse tecnologie, layout dedicato su prefisso /athlete
- **Database:** MySQL 8.0 (database: `iron_gym`)
- **Cache / code:** Redis 7
- **Auth:** Laravel Breeze (stack Livewire); API: Sanctum personal access token
- **Permissions:** spatie/laravel-permission
- **Static analysis:** Larastan livello 6
- **Code style:** Laravel Pint
- **Test:** Pest
- **Container:** Docker Compose (app, db, redis, node)
- **CI/CD:** GitHub Actions

## Convenzioni

- Lingua: italiano nel codice (commenti, messaggi), termini tecnici in inglese.
- Modelli singolari PascalCase, tabelle plurali snake_case.
- Form Request per validazione, mai inline nei controller.
- Livewire per CRUD e form complessi, Blade puro per pagine statiche.
- Migration sempre con down() implementato.
- Naming Livewire: app/Livewire/Backoffice/<Area>/<Nome> e app/Livewire/Athlete/<Nome>.

## Dominio — entità principali

**Gestionale:**
- Member: tesserato, anagrafica, certificato medico con scadenza
- SubscriptionPlan: tipologia abbonamento (durata, prezzo, ingressi)
- Subscription: abbonamento attivo di un Member
- AccessLog: registro accessi in struttura
- DumbbellInventory: inventario manubri (peso_kg, quantity_pairs, is_active); usato da PlateInventoryManager (`/backoffice/admin/plate-inventory`)

**Training core:**
- MovementPattern: lookup pattern motori (compound_pattern / joint_action)
- Muscle, Equipment: lookup tassonomia esercizi
- Exercise: catalogo esercizi, relazioni N-M su Muscle (pivot ExerciseMuscle con role e contribution_pct) e Equipment
- WorkoutTemplate: template scheda riutilizzabile (gym-wide)
- TemplateSession, TemplateSessionExercise: struttura template
- Mesocycle: istanza concreta assegnata ad atleta, generata da WorkoutTemplate
- MicrocycleWeek: settimana mesociclo (is_deload, start_date, end_date)
- TrainingSession: sessione giornaliera (planned/in_progress/completed/skipped)
- SessionExerciseGroup: raggruppamento superset/giant_set
- SessionExercise: esercizio in sessione con technique_type
- ExerciseSet: set atomico con campi planned_* e actual_* separati
- SessionFeedback, SessionExerciseFeedback: feedback post-sessione scala 0-3
- AthleteVolumeLandmark: MEV/MAV/MRV personalizzati per atleta-muscolo

**Prenotazioni:**
- TrainerAvailability: disponibilità settimanale trainer
- PtBooking: prenotazione sessione PT
- GroupClass: **definizione** del corso (slug, name, description, duration_minutes, default_capacity, room, color, is_active). Non contiene data/ora.
- ClassSchedule: palinsesto ricorrente per un corso (group_class_id, weekday 0=lun..6=dom, start_time, trainer_id nullable, valid_from, valid_until, is_active). Stesso weekday convention di TrainerAvailability.
- ClassOccurrence: **istanza datata** del corso (group_class_id, class_schedule_id nullable, date, start_time, end_time, trainer_id, capacity, status planned/cancelled/completed). Unique (class_schedule_id, date) per idempotenza command. Porta gli accessor confirmed_count, available_spots, is_full.
- class_trainer: pivot abilitazione trainer su corso (group_class_id, trainer_id).
- ClassBooking: iscrizione a ClassOccurrence con waitlist. status enum: confirmed, waitlisted, cancelled_by_athlete, cancelled_by_gym, no_show. Ha attended_at e booked_by. Unique (class_occurrence_id, member_id).

**Comunicazione:**
- Message: messaggistica interna trainer-atleta
- CommunicationTemplate, CommunicationLog: campagne e log invii
- PushSubscription: endpoint Web Push per notifiche PWA

**Tracking e analytics:**
- BodyMeasurement: misurazioni corporee periodiche
- ProgressPhoto: foto progressi per pose
- PlateInventory: inventario dischi per lato (weight_kg, quantity_pairs, color, is_active)
- PersonalRecord: PR per atleta+esercizio; record_type ENUM(e1rm, max_weight, max_reps_at_weight); questa release implementa solo e1rm
- SessionReadinessCheck: check pre-sessione (training_session_id UNIQUE); campi sleep_quality, stress_level, soreness_level, joint_status (TINYINT 0-3, 0=pessimo 3=ottimo); accessor score (0-12)

**Sistema:**
- FeedbackSubmission: feedback in-app utenti
- Feature (Pennant): feature flags per roll-out graduale
- Setting: impostazioni globali key/value (PK stringa, value JSON). Sorgente di verità per i feature flag — `group_classes` — perché `Feature::activateForEveryone()` aggiorna solo le righe già esistenti in `features`. Helper `Setting::bool($key, $default)` e `Setting::write($key, $value)`
- SyncOperation: tabella idempotenza sync offline (client_uuid UNIQUE, operation, processed_at)

## Servizi disponibili

- PlateLoadoutCalculator: calcola dischi per lato del bilanciere; greedy decrescente su PlateInventory attivi; delta_kg=0 se combinazione esatta, altrimenti combinazione per difetto
- MesocycleInstantiationService: crea gerarchia completa da template
- WeeklyVolumeCalculator: calcola hard set settimanali pesati per contribution_pct
- WeeklyProgressionService: applica progressione MEV→MRV con lettura feedback
- DeloadEvaluator: valuta i quattro trigger di deload
- KpiService: metriche aggregate per la dashboard gestore; cache Redis tag `kpi` TTL 1h
- PtBookingService: prenotazioni PT con verifica disponibilità
- ClassBookingService: enroll(ClassOccurrence, Member) → ClassBooking; cancel(ClassBooking) imposta cancelled_by_athlete e promuove waitlist; lockForUpdate su ClassOccurrence
- E1rmCalculator: formula Epley per stima 1RM
- PersonalRecordDetector: check(ExerciseSet, athleteId) → PersonalRecord|null; sincrono, pronto per migrazione a evento+listener; soglie in config/pr.php (max_reps_epley, min_sessions_before_pr)
- ExerciseSubstitutionFinder: find(Exercise) → Collection max 5 candidati; matching per stesso joint_action_id o compound_pattern_id + stesso measurement_type + non soft-deleted; overlap = somma min(pct_orig, pct_cand) su muscoli comuni; tie-break: stesso mechanic poi skill_level
- ReadinessEvaluator: evaluate(SessionReadinessCheck) → ReadinessProposal; score 0-12 (somma 4 campi 0-3); soglie da config/readiness.php (high=9 none, 5-8 reduce_5pct, <5 reduce_10pct); applyReduction(float, int): float arrotondato a 2.5 kg
- SessionRecapBuilder: build(TrainingSession, athleteId) → array con duration_minutes, tonnage_kg (set working completati, warmup esclusi), sets_completed/sets_prescribed, prs (PersonalRecord nel range started_at..completed_at), top_muscles (top 3 per SUM contribution_pct su set completati non-warmup). Cinque query, nessun N+1.
- AccessService: check-in con `DB::transaction + lockForUpdate`; idempotency window configurabile; CheckinResult readonly + CheckinFailure enum.

## Observers

Registrati in `AppServiceProvider`. Tutti in `app/Observers/`.

- ExerciseObserver (Exercise): flush cache tag `exercises` su create/update/delete
- PtBookingObserver (PtBooking): invalida cache slot trainer e tag KPI su saved/deleted. Nessuna notifica inviata.
- SubscriptionObserver (Subscription): invalida cache KPI tag `kpi` su create/update
- TrainerAvailabilityObserver (TrainerAvailability): ricalcola slot disponibili su saved/deleted
- TrainingSessionObserver (TrainingSession): aggiorna status, started_at, completed_at su update

## Decisioni architetturali fisse

- Single-tenant: niente gym_id.
- movement_patterns è tabella lookup con category (compound_pattern / joint_action).
- CHECK XOR su exercises: esattamente una tra compound_pattern_id e joint_action_id valorizzata.
- Mesociclo snapshottato all'istanziamento: modifiche al template non si propagano.
- Set unilaterali: un ExerciseSet per coppia di lati, niente granularità DX/SX nell'MVP.
- Feedback post-sessione scala 0-3.
- Ruoli spatie: atleta, trainer, gestore, receptionist.

## Mappa componenti e route

La mappa completa di tutti i componenti Livewire (~50), le route backoffice e atleta,
gli observers, i seeder e gli artisan commands è in:

**`docs/architecture/component-map.md`**

Leggila prima di aggiungere nuovi componenti o route per evitare conflitti e seguire i pattern esistenti.

**Componenti backoffice non nella component-map (trovati in codice):**
- `OpeningHoursManager` (`/backoffice/settings/opening-hours`): CRUD orari apertura settimanali + eccezioni per data specifica.
- `GlobalSearch` (`/backoffice/search`): ricerca live atleti/trainer/template, min 2 caratteri.

**Nota architetturale:** le view Livewire usano wrapper `<div>` (non `@extends`).
Il layout è gestito con `->layout('layouts.backoffice')` nel `render()`. Questo pattern è necessario per embeddare componenti via `@livewire` (es. in `AthleteProfile`).
Exercise model usa `getRouteKeyName() = 'slug'` (route binding su slug).

## WorkoutSession — interazioni chiave (Release 01 + 06)

**Componente:** `app/Livewire/Athlete/WorkoutSession.php`  
**View:** `resources/views/livewire/athlete/workout-session.blade.php` + partial `partials/exercise-card.blade.php`

| Metodo | Descrizione |
|---|---|
| `quickLog($setId)` | Copia planned→actual rispettando `measurement_type`; non resetta `completed_at` se già valorizzato |
| `completeSet($setId)` | Salva valori digitati manualmente; non resetta `completed_at` se già valorizzato |
| `generateWarmup($seId)` | Crea set is_warmup=1: 50/70/85% arrotondati a 2.5kg; sotto 40kg solo 50%; idempotente |
| `deleteWarmupSet($setId)` | Rimuove singolo set warmup; rifiuta working set con 404 |
| `loadPreviousPerformance()` | Singola query aggregata, riempie `$previousPerformance[exercise_id][set_index]` |
| `openSubstitutionModal($seId)` | Blocca se set working completati; chiama ExerciseSubstitutionFinder; popola `$substitutionCandidates` come array scalare |
| `confirmSubstitution($slug)` | Aggiorna `exercise_id`, setta `substituted_from_exercise_id`; mantiene set e prescrizione invariati; blocco doppio su set completati |
| `submitReadiness($sleep, $stress, $soreness, $joint, $note)` | Salva SessionReadinessCheck; calcola ReadinessProposal; traccia in `trainer_notes`; se outcome != none mostra `$modulationProposal` |
| `skipReadiness()` | Salta check, chiama `startSession()` direttamente |
| `acceptModulation()` | Aggiorna `planned_weight_kg` set non completati + elimina set extra (fascia low); poi `startSession()` |
| `rejectModulation()` | Avvia sessione senza modificare i carichi |
| `startSession()` (private) | Transiziona `planned → in_progress` con `started_at` |
| `completeSession()` | Transiziona `in_progress → completed` con `completed_at`; mostra `SessionFeedbackForm` embedded |

**Flusso post-completamento:** `completeSession()` → `$showFeedback=true` → `SessionFeedbackForm` → `save()`/`skip()` → redirect `/athlete/session/{id}/recap` → `SessionRecap` mostra card + export PNG.

**Alpine store `restTimer`** (workout-session.blade.php): `start(sec)`, `skip()`, `fmt(s)`. Vibrazione + Notification API allo scadere. Barra fissa bottom. Per cluster usa `intra_cluster_rest_sec`.

**`$previousPerformance`**: proprietà pubblica array, serializzata Livewire, usata dal partial per mostrare "prec: Xkg × Y @ RIR Z" sotto ogni working set.

## Stato sviluppo

Step 1-10 implementati. Release 01-31, UX01-07, API01-04 completate. **v1.2.4** (2026-08-30), **v1.2.4+** (2026-08-31), **API01-04** (2026-09-01).

**Suite corrente:** 624 test (624 pass / 6 skipped). **PHPStan:** livello 6, 0 errori. **Pint:** conforme.

Storico completo release: **`CHANGELOG.md`**. Dettaglio per release: **`docs/architecture/stato-sviluppo.md`**.

Prossima attività: API05 (abbonamenti write) o altra feature su richiesta.

## Architettura offline

**Strategia:** degraded mode — online usa Livewire standard, offline intercetta le azioni in Alpine e le accoda in IndexedDB.

**Alpine store `syncQueue`** (in workout-session.blade.php):
- `enqueue(operation, payload)`: salva in IDB con `crypto.randomUUID()` come `client_uuid`
- `flush(wire)`: POST batch a `/athlete/session/sync`, poi `wire.$refresh()`
- `isPending(setId)`: controlla se il set ha ops in coda
- Retry con backoff esponenziale: 2s → 4s → 8s ... max 30s
- `window._igWire`: ref globale al `$wire` Livewire, impostato in `x-init` del root div

**Endpoint sync:** `POST /athlete/session/sync` (guard `web`, middleware `auth + role:atleta`)
- Accetta `operations[]` con `client_uuid`, `operation`, `client_timestamp` (ms epoch), `payload`
- Operazioni: `quick_log`, `complete_set`, `generate_warmup`, `delete_warmup`
- Idempotenza: `sync_operations.client_uuid UNIQUE` — replay ignorato con `status: skipped`
- Conflitto: `completed_at` server (ms) > `client_timestamp` → `status: skipped_conflict` (last-write-wins server)
- Ownership: `whereHas('sessionExercise.session.week.mesocycle', athlete_id)` su ogni set

**Service worker v2:**
- Statici: stale-while-revalidate
- `/athlete/session/*`: network-first con fallback cache
- Livewire e pagine dinamiche: network-only, nessuna cache

## Setup pilota — dati e procedure

### Seeder pilota (idempotenti)

```bash
php artisan db:seed --class=PilotSeeder          # piani abbonamento + account gestore
php artisan db:seed --class=PilotTemplateSeeder  # template PPL ipertrofia 4 sett.
php artisan db:seed --class=FunctionalTestSeeder # scenari test funzionale (non-production)
```

### Account pilota locale

- Gestore: `gestore@iron-gym.test` / `changeme` (da `.env` PILOT_MANAGER_EMAIL/PASSWORD)
- Trainer demo: `trainer@trainer.trainer`

### Feature flags pilota (impostati via SettingsFlagSeeder)

Tutti i flag usano `Setting::bool(key, default) && <condizione_scope>`.
Toggle sempre via `Setting::write` + `Feature::purge` (non `activateForEveryone`).

| Flag | Chiave `settings` | Stato pilota | Platea (quando acceso) | Gruppo |
|---|---|---|---|---|
| `group_classes` | `group_classes_enabled` | ON | Tutta la palestra | Moduli |
| `messaging` | `messaging_enabled` | ON | Tutta la palestra | Moduli |
| `pt_bookings` | `pt_bookings_enabled` | ON | Tutta la palestra | Moduli |
| `financial_reports` | `financial_reports_enabled` | ON | Solo gestore | Sistema |
| `periodization_engine` | `periodization_engine_enabled` | ON | Gestore + trainer beta | Sistema |
| `readiness_check` | `readiness_check_enabled` | ON | Atleti | Sessione atleta |
| `exercise_substitution` | `exercise_substitution_enabled` | ON | Atleti | Sessione atleta |
| `session_recap` | `session_recap_enabled` | ON | Atleti | Sessione atleta |
| `personal_records` | `personal_records_enabled` | ON | Atleti | Sessione atleta |
| `weekly_volume` | `weekly_volume_enabled` | ON | Atleti | Sessione atleta |
| `plate_calculator` | `plate_calculator_enabled` | ON | Atleti (nessun gating point, riservato a usi futuri) | Sessione atleta |
| `push_notifications` | `push_notifications_enabled` | OFF | Atleti e trainer | Sistema |
| `outbound_notifications` | `outbound_notifications_enabled` | ON | Tutta la palestra (flag globale) | Sistema |
| `in_app_feedback` | `in_app_feedback_enabled` | OFF | Tutti | Sistema |
| `public_api` | `public_api_enabled` | OFF | Account di servizio (api_client) | Sistema |

Per modificare flags: backoffice → Impostazioni → Funzioni (solo gestore).

### Procedura registrazione atleta pilota

**1. Crea tesserato + account** — Tesserati → Nuovo tesserato
   - Campi obbligatori: Cognome, Nome, Email, Scadenza cert. medico
   - Spunta **"Crea account accesso app"** → password (min. 8 caratteri)

**2. Crea abbonamento** — Abbonamenti → Nuovo abbonamento
   - Seleziona tesserato + piano + data inizio → scadenza calcolata automaticamente
   - Colonne DB: `started_at` / `expires_at` (non start_date/end_date)

**3. Assegna mesociclo PPL** — Mesocicli → Assegna → template + atleta + data inizio → Conferma

### Template PPL — struttura

`database/seeders/PilotTemplateSeeder.php` — "PPL Ipertrofia — Intermediato (4 sett.)"

- 3 sessioni/sett: Push / Pull / Legs
- W1: 3+3 serie | W2: 4+3 | W3: 4+4 | W4 deload: 2+2 @RIR+1
- 12 TemplateSession, 200 ExerciseSet per mesociclo istanziato

## Catalogo esercizi — SQLite di riferimento

`database/database.sqlite` contiene catalogo completo queryabile senza MySQL:
- Tabelle: `movement_patterns` (27), `muscles` (26), `equipment` (14), `exercises` (83), `exercise_muscle` (259), `exercise_equipment` (108)
- Colonna `execution_description` su `exercises` con testo esecuzione per tutti e 83
- Script rigenerazione: `.claude/scripts/build_exercises_sqlite.py` (stdlib Python, sorgente unica: `exercises_seed.sql`)

Usare sqlite3 o DBeaver per interrogarlo. Non usato dai test (quelli usano `:memory:`).

## Documenti di dominio

Disponibili in `.claude/docs/domain/` ma NON caricati automaticamente. Richiedili esplicitamente:
- `step-0-discovery.md` — ERD, schema SQL, regole progressione
- `exercises-catalog.md` — catalogo 83 esercizi (tassonomia, muscoli; SQL rimosso → dati in database.sqlite)
- `glossary.md` — terminologia BB e tassonomia (documento corto, ok includerlo)

## Brand identity PWA atleta (UX01)

Dark theme di default; light theme via toggle (localStorage + `data-theme` su `<html>`).

**Layout:** `resources/views/layouts/athlete.blade.php` — NON usa AdminLTE. Standalone.

**CSS entry point:** `public/css/athlete.css` (statico). Struttura: design tokens → base → navigazione → componenti legacy → componenti `ig-*` → volume. Documentazione design system: `docs/architecture/ui-atleta.md`.

**Componenti Blade (namespace `x-athlete.*`, path `resources/views/components/athlete/`):**
- `x-athlete.button` — varianti primary/secondary/ghost/danger; spinner wire:loading integrato
- `x-athlete.card` — superficie base; props `padding`, `mb`, `tag`
- `x-athlete.stat` — label + valore numerico grande; props `label`, `unit`
- `x-athlete.badge` — status pill; prop `status` → classe `ig-badge--{status}`
- `x-athlete.input-number` — input numerico con `inputmode`; prop `stepper` per bottoni +/−

**Vite bundle atleta:** `@vite(['resources/css/app.css', 'resources/js/app.js'])` — `app.css` = solo Tailwind base utilities; `app.js` = vuoto. Il backoffice NON usa `@vite()`.

## Brand identity backoffice

Layer CSS isolato sopra AdminLTE 3.x — nessun fork del tema.

**Palette:** Accent `#E85D04` | Sidebar `#1A1A2E` | Sidebar header `#13132A`  
**Font:** Titoli `Oswald` 400/600/700 | Corpo `Inter` 400/500/600

**File:**
- `public/css/iron-gym-brand.css` — override scoped su `body.iron-gym-brand`
- `public/css/backoffice.css` — utilities: `filter-w-xs/sm/md/lg`, `table-actions`, `.skip-link`
- `public/images/iron-gym-logo.svg` — dumbbell icon 32×32 arancio

**Attivazione:** `config/adminlte.php` → `'classes_body' => 'iron-gym-brand'`

**Convenzioni UI:**
- Bottoni azione tabella: `btn-sm`
- Errori form: `is-invalid` + `invalid-feedback`
- Width filtri: classi `filter-w-*`
- Modali custom: `role="dialog"` + `aria-modal="true"` + `aria-labelledby`
- Bottoni icon-only: `aria-label` obbligatorio

## Cosa NON fare

- Non proporre Vue.js, Inertia, SPA.
- Non proporre Filament, Nova, Backpack.
- Non introdurre multi-tenancy.
- Non aggiungere colonne o tabelle senza discuterne prima.
- Non usare emoji nel codice o nei commenti.
- Non creare model Eloquent chiamati `Workout` o `WorkoutExercise`. `app/Livewire/Athlete/WorkoutSession.php` è un componente Livewire, non un Model.

## Superficie API HTTP JSON

Prefisso: `/api/v1`. Auth: Sanctum personal access token (`Authorization: Bearer <token>`).  
Documentazione: `docs/api/` (assessment, piano release, convenzioni, `openapi.yaml`).  
Swagger UI: `/backoffice/settings/api-docs` (solo gestore). Download YAML: `/backoffice/settings/api-docs/openapi.yaml`.

**Kill switch:** flag `public_api`, chiave settings `public_api_enabled`, default `false`. Spento → 503 JSON su tutte le route tranne `/ping`.

**Account di servizio:** `is_service_account = true` su `users`, ruolo `api_client`. Non autenticabili via browser. Non compaiono nelle liste `User::role('atleta')` ecc.

**Abilities token:** namespace separato dai gate web (es. `members:read`, `access-logs:write`). Mai delegare ai gate role-based dall'API.

**Formato errori:** `message` + `code` stabile. `errors` solo per 422. Nessuno stack trace in produzione.

| Code | HTTP | Causa |
|---|---|---|
| `unauthenticated` | 401 | Token assente/revocato |
| `forbidden` | 403 | Ability mancante |
| `not_found` | 404 | Risorsa inesistente |
| `validation_failed` | 422 | Payload non valido |
| `rate_limited` | 429 | Rate limit superato |
| `cert_invalid` | 422 | Cert. medico scaduto/mancante (check-in) |
| `subscription_inactive` | 422 | Nessun abbonamento attivo (check-in) |
| `accesses_exhausted` | 422 | Accessi residui esauriti (check-in) |
| `api_disabled` | 503 | Kill switch spento |
| `module_disabled` | 503 | Flag di modulo spento |

**Comandi artisan:**

```bash
php artisan api:create-service-account <consumer-slug>
php artisan api:issue-token <consumer-slug> --name="<desc>" --abilities="members:read"
php artisan api:tokens [--consumer=<slug>] [--revoke=<token-id>]
```

**Rate limiting:** Redis, 60 req/min autenticato / 10 req/min anonimo. Config: `config/api.php` o env `API_RATE_LIMIT_AUTH` / `API_RATE_LIMIT_ANON`.

**Endpoint disponibili (API01-04):** vedi `docs/api/03-endpoints.md` per lista completa con abilities e filtri.

| Metodo | Path | Ability | Note |
|---|---|---|---|
| GET | /api/v1/ping | — | Esente da auth e kill switch |
| GET | /api/v1/me | — | — |
| GET | /api/v1/subscription-plans | `subscription-plans:read` | — |
| GET | /api/v1/members | `members:read` | filtri: search, is_active, cert_expiry_before |
| GET | /api/v1/members/{id} | `members:read` | — |
| GET | /api/v1/members/{id}/subscription | `members:read` | — |
| GET | /api/v1/access-logs | `access-logs:read` | cap 31 gg |
| POST | /api/v1/access-logs | `access-logs:write` | idempotenza 5 min |
| GET | /api/v1/exercises | `exercises:read` | filtri muscle/equipment/mechanic |
| GET | /api/v1/exercises/{slug} | `exercises:read` | — |
| GET | /api/v1/group-classes | `group-classes:read` | gated: `group_classes` |
| GET | /api/v1/class-occurrences | `group-classes:read` | gated: `group_classes` |
| GET | /api/v1/class-bookings | `class-bookings:read` | gated: `group_classes` |
| POST | /api/v1/class-bookings | `class-bookings:write` | idempotente su AlreadyEnrolled |
| DELETE | /api/v1/class-bookings/{booking} | `class-bookings:write` | cancel atleta |

## Comandi utili

```bash
# Ambiente dev
docker compose up -d
php artisan serve
npm run dev
php artisan queue:work redis --queue=default
php artisan schedule:work

# DB
php artisan migrate:fresh --seed

# Qualità
./vendor/bin/pest
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pint --test

# Go-live
php artisan pilot:init

# PWA
php artisan pwa:generate-icons

# SQLite esercizi (dev tool)
python .claude/scripts/build_exercises_sqlite.py
```
