# iron-gym

Software di gestione per palestra di bodybuilding e fitness con focus sulla personalizzazione dell'allenamento e autoregolazione del volume.

## Stack tecnico

- **Backend:** PHP 8.3, Laravel 11.x
- **Frontend backoffice:** Livewire 3 + Alpine.js, tema AdminLTE 3.x
- **App atleta:** stesse tecnologie, layout dedicato su prefisso `/athlete`
- **Database:** MySQL 8.0 (database: `iron_gym`)
- **Cache / queue:** Redis 7
- **Auth:** Laravel Breeze (stack Livewire)
- **Permissions:** spatie/laravel-permission
- **Static analysis:** Larastan livello 6
- **Code style:** Laravel Pint
- **Test:** Pest
- **Container:** Docker Compose (app, db, redis, node)
- **CI/CD:** GitHub Actions

## Funzionalità implementate

**Gestionale:** anagrafica tesserati con certificati medici e scadenze, piani abbonamento (durata, prezzo, ingressi inclusi), abbonamenti attivi con rinnovo, registro accessi in struttura.

**Training:** catalogo esercizi (83 esercizi, 26 muscoli, 14 equipment, 27 movement pattern) con tassonomia completa e ruoli muscolari. Template di scheda riutilizzabili (gym-wide). Mesocicli assegnati agli atleti, generati da template con snapshot al momento dell'istanziamento. Logging sessioni con set pianificati e set effettivi separati, supporto superset e giant set, tecniche speciali. Quick-log one-tap, previous performance inline, rest timer globale Alpine, generatore warm-up automatico. Periodizzazione con volume landmarks per atleta-muscolo (MEV/MAV/MRV), progressione automatica settimana per settimana, trigger di deload. Feedback post-sessione su scala 0-3 con autoregolazione del carico. Sostituzione esercizio guidata in-sessione con matching per pattern motore e overlap muscolare (max 5 candidati). Check readiness pre-sessione (sonno/stress/dolori/articolazioni 0-3) con proposta modulazione carichi -5%/-10% arrotondata a 2.5 kg.

**Personal records:** rilevamento automatico PR e1RM (formula Epley) al completamento di ogni set — online e offline. Toast auto-dismiss in sessione. Lista storica PR per esercizio.

**Plate calculator:** calcolo dischi per lato del bilanciere su inventario reale; algoritmo greedy decrescente con combinazione per difetto. Gestione inventario nel backoffice.

**Volume visuale:** body map SVG fronte/retro (25 muscoli colorati per intensità). Barre orizzontali volume settimanale vs landmark MEV/MAV/MRV per atleta. Selettore settimana mesociclo.

**Riepilogo sessione:** card post-sessione con durata, tonnellaggio, set completati/prescritti, PR ottenuti, top 3 muscoli allenati. Export PNG via Web Share API con fallback download diretto. Accessibile anche dallo storico allenamenti.

**PWA offline-first:** service worker stale-while-revalidate per asset, network-first con cache fallback per pagine sessione. Coda operazioni IndexedDB con flush automatico al ripristino connettività, idempotenza server-side via `sync_operations.client_uuid`.

**Tracking corporeo:** misurazioni periodiche (peso, circonferenze, plicometria), foto progressi per pose standard, grafici andamento.

**Prenotazioni:** disponibilità settimanale trainer, prenotazioni sessioni PT, corsi collettivi con gestione lista d'attesa.

**Messaggistica e notifiche:** messaggistica interna trainer-atleta, campagne di comunicazione con log invii, notifiche push PWA (Web Push con VAPID), notifiche automatiche per scadenze certificati e abbonamenti.

**Reportistica:** dashboard gestore con KPI (ingressi, fatturato, churn, utilizzo), report finanziari, export dati.

**Sistema:** feature flags con Laravel Pennant per roll-out graduale, feedback in-app utenti, health check endpoint, backup automatico con retention configurabile.

## Setup sviluppo

```bash
# 1. Clona il repository
git clone <repo-url> iron-gym
cd iron-gym

# 2. Avvia i container (MySQL 8 + Redis 7)
docker compose up -d

# 3. Configura l'ambiente
cp .env.example .env
# Imposta DB_DATABASE=iron_gym, DB_USERNAME, DB_PASSWORD,
# REDIS_HOST=127.0.0.1 (o il nome del container se usi la rete Docker)

# 4. Dipendenze
composer install
npm install

# 5. Genera la chiave applicazione
php artisan key:generate

# 6. Migrazione e seed completo (catalogo esercizi + dati demo)
php artisan migrate:fresh --seed

# 7. Avvia i quattro processi (terminali separati)
php artisan serve          # http://localhost:8000
npm run dev                # asset Vite con HMR
php artisan queue:work redis --queue=default
php artisan schedule:work
```

## Comandi rapidi

```bash
# Test (Pest)
./vendor/bin/pest

# Smoke test su staging (richiede MySQL reale)
./vendor/bin/pest tests/Feature/SmokeTest.php --no-coverage

# Code style check
./vendor/bin/pint --test

# Code style fix
./vendor/bin/pint

# Static analysis (Larastan L6)
./vendor/bin/phpstan analyse --memory-limit=512M

# Health check
curl http://localhost:8000/health

# Go-live: inizializza piani abbonamento reali e account gestore
php artisan pilot:init
```

## Struttura

- **app/Livewire/Backoffice/** — Componenti backoffice (Exercises, Templates, Mesocycles, Members, Bookings, Reports...)
- **app/Livewire/Athlete/** — Componenti app atleta
- **app/Services/** — Servizi dominio (MesocycleInstantiationService, WeeklyProgressionService, KpiService, PlateLoadoutCalculator, PersonalRecordDetector, ExerciseSubstitutionFinder, ReadinessEvaluator, SessionRecapBuilder...)
- **resources/views/livewire/** — Template Blade dei componenti
- **database/migrations/** — Una per tabella, `down()` sempre implementato
- **database/seeders/sql/** — `exercises_seed.sql` (83 esercizi)
- **tests/Feature, tests/Unit** — Pest (naming descrittivo)
- **docs/domain/** — Documentazione di dominio (ERD, catalogo, glossario)

## Feature flags (Laravel Pennant)

| Flag | Condizione | Default |
|---|---|---|
| `periodization_engine` | gestore o beta trainer (FEATURE_BETA_TRAINERS) | per utente |
| `push_notifications` | atleti e trainer | per utente |
| `group_classes` | FEATURE_GROUP_CLASSES=true | false globale |
| `financial_reports` | solo gestore | per utente |

Gestione via backoffice: `/backoffice/admin/feature-flags` (solo gestore).

## Ruoli e accesso

| Persona | Area | Accesso |
|---|---|---|
| Atleta | /athlete | Vede schede, esegue workout (quick-log, warm-up, rest timer), check readiness, sostituzione esercizi, registra feedback, consulta riepilogo sessione, body map volume, record personali, plate calculator |
| Trainer | Backoffice | Crea template, assegna mesocicli, monitora, autoregola |
| Gestore | Backoffice | KPI, dati finanziari, staff, listini. Privilegi trainer. |
| Receptionist | Backoffice | Check-in, anagrafica, certificati, abbonamenti. Training in lettura |

## Variabili .env rilevanti

```
# Feature flags
FEATURE_BETA_TRAINERS=trainer1@email.com,trainer2@email.com
FEATURE_GROUP_CLASSES=false

# Push PWA
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...

# Feedback in-app
FEEDBACK_EMAIL=feedback@iron-gym.local

# Flare error tracking
FLARE_KEY=

# PR detection
PR_MAX_REPS_EPLEY=12
PR_MIN_SESSIONS=3

# Go-live
PILOT_MANAGER_EMAIL=gestore@palestra.it
PILOT_MANAGER_PASSWORD=password-sicura
```

## Secrets GitHub Actions

```
STAGING_HOST        # IP/hostname server staging
STAGING_USER        # Utente SSH
STAGING_KEY         # Chiave privata SSH (RSA/ED25519 PEM)
```

---

**Vedi CLAUDE.md per convenzioni di codice e CHANGELOG.md per la storia del progetto.**
