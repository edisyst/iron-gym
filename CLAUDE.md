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

Prossima attività: test pilota con dati reali.

## Documenti di dominio

Disponibili in docs/domain/ ma NON caricati automaticamente per non saturare
il contesto. Richiedili esplicitamente quando servono:
- docs/domain/step-0-discovery.md — ERD, schema SQL, regole di progressione
- docs/domain/exercises-catalog.md — catalogo 83 esercizi con seed SQL
- docs/domain/glossary.md — terminologia BB e tassonomia (documento corto, ok includerlo)

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
```
