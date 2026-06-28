# iron-gym

Gestionale per palestra di bodybuilding e fitness. Copre: anagrafica tesserati,
abbonamenti, accessi, libreria esercizi, schede di allenamento (template e mesocicli),
logging sessioni atleta, periodizzazione con volume landmarks, tracking corporeo,
prenotazioni PT e corsi, messaggistica trainer-atleta, notifiche automatiche,
reportistica gestore, feature flags.

## Stack tecnico

- **Backend:** PHP 8.3, Laravel 11.x
- **Frontend backoffice:** Livewire 3 + Alpine.js, tema AdminLTE 3.x
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
- Form Request per la validazione, mai inline nei controller.
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
- Exercise: catalogo esercizi con relazioni N-M su Muscle (pivot ExerciseMuscle con role e contribution_pct) e Equipment
- WorkoutTemplate: template di scheda riutilizzabile (gym-wide)
- TemplateSession, TemplateSessionExercise: struttura del template
- Mesocycle: istanza concreta assegnata a un atleta, generata da WorkoutTemplate
- MicrocycleWeek: settimana del mesociclo (is_deload, start_date, end_date)
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
- ClassBooking: iscrizione a corso con waitlist

**Comunicazione:**
- Message: messaggistica interna trainer-atleta
- CommunicationTemplate, CommunicationLog: campagne e log invii
- PushSubscription: endpoint Web Push per notifiche PWA

**Tracking e analytics:**
- BodyMeasurement: misurazioni corporee periodiche
- ProgressPhoto: foto progressi per pose

**Sistema:**
- FeedbackSubmission: feedback in-app utenti
- Feature (Pennant): feature flags per roll-out graduale

## Servizi disponibili

- MesocycleInstantiationService: crea la gerarchia completa da template
- WeeklyVolumeCalculator: calcola hard set settimanali pesati per contribution_pct
- WeeklyProgressionService: applica progressione MEV→MRV con lettura feedback
- DeloadEvaluator: valuta i quattro trigger di deload
- KpiService: metriche aggregate per la dashboard gestore
- PtBookingService: prenotazioni PT con verifica disponibilità
- ClassBookingService: iscrizioni corsi con gestione waitlist
- E1rmCalculator: formula Epley per stima 1RM

## Decisioni architetturali fisse

- Single-tenant: niente gym_id.
- movement_patterns è tabella di lookup con category (compound_pattern / joint_action).
- CHECK XOR su exercises: esattamente una tra compound_pattern_id e joint_action_id valorizzata.
- Mesociclo snapshottato all'istanziamento: modifiche al template non si propagano.
- Set unilaterali: un ExerciseSet per coppia di lati, niente granularità DX/SX nell'MVP.
- Feedback post-sessione su scala 0-3.
- Ruoli spatie: atleta, trainer, gestore, receptionist.

## Componenti Livewire aggiunti (post step 10)

**Backoffice:**
- `Backoffice/Athletes/AthleteProfile` — contenitore profilo atleta con tab Alpine.js (storico, analytics, misurazioni, landmarks, messaggi). Route: `backoffice.athletes.profile`.
- `Backoffice/Athletes/AthleteSessionHistory` — storico sessioni atleta lato backoffice; filtra per `athlete_id`, mostra trainer, set, durata, feedback con badge 0-3, dettaglio inline con e1RM.
- `Backoffice/Exercises/ExerciseDetail` — scheda tecnica esercizio (già esistente, potenziata): breadcrumb, immagine/placeholder, card Identità, card Attrezzatura, card Muscolare con progress bar AdminLTE (bg-danger/warning/info), card Esecuzione, card Video. Route: `backoffice.exercises.show` (slug binding). Exercise model usa `getRouteKeyName() = 'slug'`.

**Nota architetturale:** le view Livewire che usavano `@extends('adminlte::page')` sono state convertite a wrapper `<div>` (pattern standard Livewire 3). Il layout standalone è gestito con `->layout('layouts.backoffice')` nel `render()`.

## Stato sviluppo

Tutti gli step 1-10 sono stati implementati. Il sistema è in fase di verifica
funzionale e test pre-pilota.

Bug risolti in fase di verifica:
- Cache equipment in ExerciseList: Eloquent Collection serializzata su file cache
  produceva `__PHP_Incomplete_Class` al deserialize. Fix: cache come array plain.
- CACHE_STORE era `file`: portato a `redis` per supportare `Cache::tags()` usato
  in ExerciseObserver e per coerenza con QUEUE_CONNECTION=redis.
- APP_URL era `localhost:8000`: corretto a `iron-gym.test` (Laragon).

Test end-to-end del flusso training core verificati (2026-06-22): AthleteHistoryTest 4/4, suite 90/96, PHPStan 0 errori, Pint conforme.

ExerciseDetailPage implementata (2026-06-25): ExerciseDetailPageTest 4/4, PHPStan 0 errori, Pint conforme.

Revisione codice staged completata (2026-06-27): security (IDOR SessionFeedbackForm/TemplateBuilder, middleware backoffice, FK mesocycles, MessageThread), performance (cache lookup statici, deload signal fuori da render, RIR drift subquery SQL, index exercise_sets.completed_at), test DeloadEvaluator 5/5, 6 factory mancanti. Suite: 96/102, PHPStan 0 errori, Pint conforme.

Setup pilota avviato (2026-06-28): PilotSeeder eseguito (4 piani reali, account gestore@iron-gym.test), feature flags impostati (financial_reports ON, gli altri OFF), PilotTemplateSeeder aggiunto.

Flusso assegnazione verificato (2026-06-28): mesociclo PPL assegnato ad Atleta Test (ID=9, 4 settimane, 12 sessioni, 200 set). Dashboard atleta mostra Push/Pull/Legs pianificate. Receptionist bloccato con 403 su /assign. Bug fix: route `{mesocycle}` → `{mesocycleId}` (mismatch con mount() causava 500 su ogni dettaglio mesociclo).

Registrazione atleta pilota completata (2026-06-28): Marco Rossi registrato (Member ID=7, User ID=11, Mensile, PPL attivo). MemberForm potenziato con sezione "Crea account accesso app" — crea User+ruolo atleta+user_id in un unico submit. Procedura registrazione ora 100% via UI backoffice.

Verifica E2E pilota completata (2026-06-28): Marco Rossi login → dashboard atleta mostra PPL Settimana 1 di 4 con Push/Pull/Legs pianificate → sessione Push aperta con esercizi e set editabili. Flusso registrazione-abbonamento-mesociclo-sessione verificato end-to-end. Bug fix: `email_verified_at` non in `#[Fillable]` di User — ora impostata via assegnazione diretta dopo `User::create()`.

Prossima attività: raccogliere feedback dai primi atleti pilota dopo la prima sessione.

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
| `periodization_engine` | OFF | dopo 2 settimane di test manuale |
| `push_notifications` | OFF | dopo verifica service worker su dispositivo reale |
| `group_classes` | OFF | solo se la palestra usa corsi collettivi |

Per modificare flags: backoffice → Admin → Feature Flags (solo gestore).

### Procedura registrazione atleta pilota

Sequenza completa — tutto via backoffice UI:

**1. Crea tesserato + account** — Tesserati → Nuovo tesserato
   - Campi obbligatori: Cognome, Nome, Email, Scadenza cert. medico
   - Spunta **"Crea account accesso app"** → inserisci password (min. 8 caratteri)
   - Il sistema crea User con ruolo `atleta` e collega `user_id` in automatico

**2. Crea abbonamento** — Abbonamenti → Nuovo abbonamento
   - Seleziona tesserato + piano + data inizio → la scadenza si calcola in automatico

**3. Assegna mesociclo PPL** — Mesocicli → Assegna mesociclo
   - Seleziona atleta + template + data inizio → Avanti → Conferma

### Template PPL — struttura

`database/seeders/PilotTemplateSeeder.php` — "PPL Ipertrofia — Intermediato (4 sett.)"

- 3 sessioni/sett: Push (petto/spalle/tricipiti), Pull (schiena/bicipiti), Legs (gambe/glutei/polpacci)
- W1: 3 serie compound + 3 iso | W2: 4+3 | W3: 4+4 | W4 deload: 2+2 @RIR+1
- 12 TemplateSession, 200 ExerciseSet per mesociclo istanziato

**Flusso assegnazione:** backoffice → Mesocicli → Assegna → scegli template + atleta + data inizio.

## Catalogo esercizi — SQLite di riferimento

`database/database.sqlite` contiene il catalogo completo queryabile senza MySQL:
- Tabelle: `movement_patterns` (27), `muscles` (26), `equipment` (14),
  `exercises` (83), `exercise_muscle` (259), `exercise_equipment` (108)
- Colonna `execution_description` su `exercises` con testo esecuzione per tutti e 83
- Script di rigenerazione: `.claude/scripts/build_exercises_sqlite.py`
  (stdlib Python, nessuna dipendenza extra; sorgente unica: `exercises_seed.sql`)

Usare sqlite3 o DBeaver per interrogarlo. Non è usato dai test (quelli usano `:memory:`).

## Documenti di dominio

Disponibili in .claude/docs/domain/ ma NON caricati automaticamente per non saturare
il contesto. Richiedili esplicitamente quando servono:
- .claude/docs/domain/step-0-discovery.md — ERD, schema SQL, regole di progressione
- .claude/docs/domain/exercises-catalog.md — catalogo 83 esercizi (tassonomia, muscoli, note metodologiche; SQL rimosso → dati in database.sqlite)
- .claude/docs/domain/glossary.md — terminologia BB e tassonomia (documento corto, ok includerlo)

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

# DB
php artisan migrate:fresh --seed

# Qualità
./vendor/bin/pest
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pint --test

# Scheduler (dev)
php artisan schedule:work

# Rigenera SQLite di riferimento esercizi (AI/dev tool, non prod; stdlib Python)
python .claude/scripts/build_exercises_sqlite.py
```
