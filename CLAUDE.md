# iron-gym

Gestionale palestra bodybuilding/fitness. Copre: anagrafica tesserati, abbonamenti, accessi, libreria esercizi, schede allenamento (template e mesocicli), logging sessioni atleta, periodizzazione con volume landmarks, tracking corporeo, prenotazioni PT/corsi, messaggistica trainer-atleta, notifiche automatiche, reportistica gestore, feature flags.

## Stack tecnico

- **Backend:** PHP 8.3, Laravel 11.x
- **Frontend backoffice:** Livewire 3 + Alpine.js, tema AdminLTE 3.x + brand layer Iron Gym
- **App atleta:** stesse tecnologie, layout dedicato su prefisso /athlete
- **Database:** MySQL 8.0 (database: `iron_gym`)
- **Cache / code:** Redis 7
- **Auth:** Laravel Breeze (stack Livewire)
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
- GroupClass: corso collettivo
- ClassBooking: iscrizione corso con waitlist

**Comunicazione:**
- Message: messaggistica interna trainer-atleta
- CommunicationTemplate, CommunicationLog: campagne e log invii
- PushSubscription: endpoint Web Push per notifiche PWA

**Tracking e analytics:**
- BodyMeasurement: misurazioni corporee periodiche
- ProgressPhoto: foto progressi per pose
- PlateInventory: inventario dischi per lato (weight_kg, quantity_pairs, color, is_active)

**Sistema:**
- FeedbackSubmission: feedback in-app utenti
- Feature (Pennant): feature flags per roll-out graduale

## Servizi disponibili

- PlateLoadoutCalculator: calcola dischi per lato del bilanciere; greedy decrescente su PlateInventory attivi; delta_kg=0 se combinazione esatta, altrimenti combinazione per difetto
- MesocycleInstantiationService: crea gerarchia completa da template
- WeeklyVolumeCalculator: calcola hard set settimanali pesati per contribution_pct
- WeeklyProgressionService: applica progressione MEV→MRV con lettura feedback
- DeloadEvaluator: valuta i quattro trigger di deload
- KpiService: metriche aggregate per la dashboard gestore; cache Redis tag `kpi` TTL 1h
- PtBookingService: prenotazioni PT con verifica disponibilità
- ClassBookingService: iscrizioni corsi con gestione waitlist
- E1rmCalculator: formula Epley per stima 1RM

## Observers

Registrati in `AppServiceProvider`. Tutti in `app/Observers/`.

- ExerciseObserver (Exercise): flush cache tag `exercises` su create/update/delete
- PtBookingObserver (PtBooking): notifica atleta+trainer su conferma/cancellazione
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

Leggila prima di aggiungere nuovi componenti o route per evitare conflitti e
seguire i pattern esistenti.

**Nota architetturale:** le view Livewire usano wrapper `<div>` (non `@extends`).
Il layout è gestito con `->layout('layouts.backoffice')` nel `render()`. Questo
pattern è necessario per embeddare componenti via `@livewire` (es. in `AthleteProfile`).
Exercise model usa `getRouteKeyName() = 'slug'` (route binding su slug).

## WorkoutSession — interazioni chiave (Release 01)

**Componente:** `app/Livewire/Athlete/WorkoutSession.php`  
**View:** `resources/views/livewire/athlete/workout-session.blade.php` + partial `partials/exercise-card.blade.php`

| Metodo | Descrizione |
|---|---|
| `quickLog($setId)` | Copia planned→actual rispettando `measurement_type`; non resetta `completed_at` se già valorizzato |
| `completeSet($setId)` | Salva valori digitati manualmente; non resetta `completed_at` se già valorizzato |
| `generateWarmup($seId)` | Crea set is_warmup=1: 50/70/85% arrotondati a 2.5kg; sotto 40kg solo 50%; idempotente |
| `deleteWarmupSet($setId)` | Rimuove singolo set warmup; rifiuta working set con 404 |
| `loadPreviousPerformance()` | Singola query aggregata, riempie `$previousPerformance[exercise_id][set_index]` |

**Alpine store `restTimer`** (definito in workout-session.blade.php): `start(sec)`, `skip()`, `fmt(s)`. Avvia vibrazione + Notification API allo scadere. Barra fissa bottom. Per cluster usa `intra_cluster_rest_sec`.

**`$previousPerformance`**: proprietà pubblica array, serializzata Livewire, usata dal partial per mostrare "prec: Xkg × Y @ RIR Z" sotto ogni working set.

## Stato sviluppo

Step 1-10 tutti implementati. Sistema in verifica funzionale e test pre-pilota.

Bug risolti in verifica:
- Cache equipment in ExerciseList: Eloquent Collection serializzata su file cache produceva `__PHP_Incomplete_Class` al deserialize. Fix: cache come array plain.
- CACHE_STORE era `file`: portato a `redis` per supportare `Cache::tags()` usato in ExerciseObserver e per coerenza con QUEUE_CONNECTION=redis.
- APP_URL era `localhost:8000`: corretto a `iron-gym.test` (Laragon).

Test E2E flusso training core verificati (2026-06-22): AthleteHistoryTest 4/4, suite 90/96, PHPStan 0 errori, Pint conforme.

ExerciseDetailPage implementata (2026-06-25): ExerciseDetailPageTest 4/4, PHPStan 0 errori, Pint conforme.

Revisione codice staged completata (2026-06-27): security (IDOR SessionFeedbackForm/TemplateBuilder, middleware backoffice, FK mesocycles, MessageThread), performance (cache lookup statici, deload signal fuori da render, RIR drift subquery SQL, index exercise_sets.completed_at), test DeloadEvaluator 5/5, 6 factory mancanti. Suite: 96/102, PHPStan 0 errori, Pint conforme.

Setup pilota avviato (2026-06-28): PilotSeeder eseguito (4 piani reali, account gestore@iron-gym.test), feature flags impostati (financial_reports ON, altri OFF), PilotTemplateSeeder aggiunto.

Flusso assegnazione verificato (2026-06-28): mesociclo PPL assegnato ad Atleta Test (ID=9, 4 settimane, 12 sessioni, 200 set). Dashboard atleta mostra Push/Pull/Legs pianificate. Receptionist bloccato con 403 su /assign. Bug fix: route `{mesocycle}` → `{mesocycleId}` (mismatch con mount() causava 500 su ogni dettaglio mesociclo).

Registrazione atleta pilota completata (2026-06-28): Marco Rossi registrato (Member ID=7, User ID=11, Mensile, PPL attivo). MemberForm potenziato con sezione "Crea account accesso app" — crea User+ruolo atleta+user_id in un unico submit. Procedura registrazione ora 100% via UI backoffice.

Verifica E2E pilota completata (2026-06-28): Marco Rossi login → dashboard atleta mostra PPL Settimana 1 di 4 con Push/Pull/Legs pianificate → sessione Push aperta con esercizi e set editabili. Flusso registrazione-abbonamento-mesociclo-sessione verificato end-to-end. Bug fix: `email_verified_at` non in `#[Fillable]` di User — ora impostata via assegnazione diretta dopo `User::create()`.

Audit sicurezza v2 completato (2026-06-28): 15 fix applicati — ownership check trainer→atleta su 5 componenti backoffice (AthleteProfile, AthleteAnalytics, BodyMeasurementForm, VolumeLandmarkManager, MesocycleDetail), TrainingReport drilldown filtrato per trainer, MemberForm update bloccato per receptionist, BookingList.confirm() con trainer_id filter, MesocycleAssign verifica ruolo atleta, SessionFeedbackForm ownership in mount(), path traversal fix in ProgressPhotoController, bug overcounting sessions_count in AthleteAnalytics risolto, paginazione messaggi (limit 100). PHPStan 0 errori, Pint OK, suite 96/102. Report: docs/review/audit-codice.md.

Fix residui LOW completati (2026-06-28): WeeklyProgressionService.applyDeload() usa ultima sessione per scheduled_date invece di MAX (baseline deload corretta); progressWeek() invalida cache WeeklyVolumeCalculator dopo progressione; MesocycleInstantiationService aggiunge parametro deload_last_week (default true); ProgressPhotoUpload usa Str::uuid() + elimina vecchio file prima di sovrascrivere; TemplateBuilder.removeExercise()/toggleGroup() filtrano per template_id su query group_key; VolumeLandmarkManager.render() singola query Muscle; PilotSeeder imposta email_verified_at. PHPStan 0 errori, Pint OK, suite 96/102. Audit completo — zero finding aperti.

Revisione grafica backoffice completata (2026-06-28): audit UI + Fase 1 coerenza + Fase 2 brand identity. 9 commit. Dettagli: docs/review/audit-grafica.md. Suite 106/106, PHPStan 0, Pint OK.

Fix responsive athlete completato (2026-06-28): H4 chiuso — CSS estratto in public/css/athlete.css, sidebar nav desktop (≥1024px), breakpoints tablet (768px)/desktop (1024px)/large (1280px). Suite 106/106, PHPStan 0, Pint OK. Tutti finding HIGH/MED dell'audit grafici chiusi.

Release 01 UX sessione completata (2026-07-03): quick-log one-tap, previous performance inline, rest timer globale, warm-up generator. 15 nuovi test verde. PHPStan 0 errori, Pint OK. Suite 121/129 (8 fallimenti pre-esistenti: Vite manifest mancante + Volt auth pages, non legati a questa release).

Release 02 Plate Calculator completata (2026-07-03): PlateInventory model+migration+seeder, PlateLoadoutCalculator service (greedy decrescente), PlateInventoryManager backoffice (CRUD inline gestore), modale atleta con stack grafico dischi e selettore peso barra. 4 test Unit PlateLoadoutCalculatorTest.

Prossima attività: raccogliere feedback dai primi atleti pilota dopo prima sessione.

## Setup pilota — dati e procedure

### Seeder pilota (idempotenti)

```bash
php artisan db:seed --class=PilotSeeder          # piani abbonamento + account gestore
php artisan db:seed --class=PilotTemplateSeeder  # template PPL ipertrofia 4 sett.
```

### Account pilota locale

- Gestore: `gestore@iron-gym.test` / `changeme` (da `.env` PILOT_MANAGER_EMAIL/PASSWORD)
- Trainer demo: `trainer@trainer.trainer`

### Feature flags pilota (impostati via Pennant DB store)

| Flag | Stato pilota | Quando attivare |
|---|---|---|
| `financial_reports` | ON | attivo da subito per gestore |
| `periodization_engine` | OFF | dopo 2 settimane test manuale |
| `push_notifications` | OFF | dopo verifica service worker su dispositivo reale |
| `group_classes` | OFF | solo se palestra usa corsi collettivi |

Per modificare flags: backoffice → Admin → Feature Flags (solo gestore).

### Procedura registrazione atleta pilota

Sequenza completa — tutto via backoffice UI:

**1. Crea tesserato + account** — Tesserati → Nuovo tesserato
   - Campi obbligatori: Cognome, Nome, Email, Scadenza cert. medico
   - Spunta **"Crea account accesso app"** → inserisci password (min. 8 caratteri)
   - Sistema crea User con ruolo `atleta` e collega `user_id` in automatico

**2. Crea abbonamento** — Abbonamenti → Nuovo abbonamento
   - Seleziona tesserato + piano + data inizio → scadenza calcolata in automatico
   - Nota: colonne DB sono `started_at` / `expires_at` (non start_date/end_date)

**3. Assegna mesociclo PPL** — Mesocicli → Assegna mesociclo
   - Seleziona atleta + template + data inizio → Avanti → Conferma

### Template PPL — struttura

`database/seeders/PilotTemplateSeeder.php` — "PPL Ipertrofia — Intermediato (4 sett.)"

- 3 sessioni/sett: Push (petto/spalle/tricipiti), Pull (schiena/bicipiti), Legs (gambe/glutei/polpacci)
- W1: 3 serie compound + 3 iso | W2: 4+3 | W3: 4+4 | W4 deload: 2+2 @RIR+1
- 12 TemplateSession, 200 ExerciseSet per mesociclo istanziato

**Flusso assegnazione:** backoffice → Mesocicli → Assegna → scegli template + atleta + data inizio.

## Catalogo esercizi — SQLite di riferimento

`database/database.sqlite` contiene catalogo completo queryabile senza MySQL:
- Tabelle: `movement_patterns` (27), `muscles` (26), `equipment` (14), `exercises` (83), `exercise_muscle` (259), `exercise_equipment` (108)
- Colonna `execution_description` su `exercises` con testo esecuzione per tutti e 83
- Script rigenerazione: `.claude/scripts/build_exercises_sqlite.py` (stdlib Python, nessuna dipendenza extra; sorgente unica: `exercises_seed.sql`)

Usare sqlite3 o DBeaver per interrogarlo. Non usato dai test (quelli usano `:memory:`).

## Documenti di dominio

Disponibili in .claude/docs/domain/ ma NON caricati automaticamente per non saturare contesto. Richiedili esplicitamente quando servono:
- .claude/docs/domain/step-0-discovery.md — ERD, schema SQL, regole progressione
- .claude/docs/domain/exercises-catalog.md — catalogo 83 esercizi (tassonomia, muscoli, note metodologiche; SQL rimosso → dati in database.sqlite)
- .claude/docs/domain/glossary.md — terminologia BB e tassonomia (documento corto, ok includerlo)

## Brand identity backoffice

Layer CSS isolato e disattivabile sopra AdminLTE 3.x — nessun fork del tema.

**Palette:**
- Accent: `#E85D04` (arancio brand, shared con area atleta)
- Sidebar: `#1A1A2E` (navy scuro)
- Sidebar header: `#13132A`

**Font (Google Fonts):**
- Titoli / sidebar brand-text: `Oswald` 400/600/700
- Corpo testo: `Inter` 400/500/600

**File:**
- `public/css/iron-gym-brand.css` — override scoped su `body.iron-gym-brand` (CSS custom properties + override Bootstrap/AdminLTE)
- `public/css/backoffice.css` — utilities: `filter-w-xs/sm/md/lg`, `table-actions`, `.skip-link`
- `public/images/iron-gym-logo.svg` — dumbbell icon 32×32 arancio

**Attivazione:** `config/adminlte.php` → `'classes_body' => 'iron-gym-brand'`
**Disattivazione:** cambiare in `''` — rimuove tutto il layer in 1 riga.

**Convenzioni UI (post-audit 2026-06-28):**
- Bottoni azione tabella: `btn-sm` (non `btn-xs`)
- Errori form: `is-invalid` + `invalid-feedback` (non `text-danger small`)
- Width filtri: classi `filter-w-*` (non inline `style="width:Npx"`)
- Modali custom: `role="dialog"` + `aria-modal="true"` + `aria-labelledby`
- Bottoni icon-only: `aria-label` obbligatorio

## Cosa NON fare

- Non proporre Vue.js, Inertia, SPA.
- Non proporre Filament, Nova, Backpack.
- Non introdurre multi-tenancy.
- Non aggiungere colonne o tabelle senza discuterne prima.
- Non usare emoji nel codice o nei commenti.

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

# Go-live: inizializza piani abbonamento reali e account gestore
php artisan pilot:init

# Genera icone PWA da resources/images/icon.png
php artisan pwa:generate-icons

# Rigenera SQLite di riferimento esercizi (AI/dev tool, non prod; stdlib Python)
python .claude/scripts/build_exercises_sqlite.py
```