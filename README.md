# iron-gym

Software di gestione per palestra di bodybuilding e fitness con focus sulla personalizzazione dell'allenamento e autoregolazione del volume.

**Stack:** PHP 8.3, Laravel 11, Livewire 3 + Alpine.js, AdminLTE 3, MySQL 8, Redis 7, Vite, Pest, Larastan L6.

**Status:** tutti e 10 gli step completati. Pronto per go-live in palestra reale.

---

## Documentazione

- **CLAUDE.md** — specifiche di progetto, convenzioni, comandi, secrets GitHub Actions.
- **CHANGELOG.md** — storia completa di tutti gli step di sviluppo.
- **docs/domain/step-0-discovery.md** — modello di dominio completo (glossario BB, ERD, schema SQL, regole progressione).
- **docs/domain/exercises-catalog.md** — catalogo 83 esercizi, 26 muscoli, 14 equipment, 27 movement patterns con seed SQL.
- **docs/domain/glossary.md** — glossario rapido (terminologia, personas, tassonomia).
- **docs/devops/go-live-checklist.md** — checklist pre-go-live.

## Prerequisiti

- PHP 8.3
- Composer 2.x
- Node 20 LTS
- Docker (MySQL 8 + Redis 7)

## Setup dev

```bash
# 1. Avvia container
docker compose up -d

# 2. Configura env
cp .env.example .env
php artisan key:generate

# 3. Dipendenze
composer install
npm install

# 4. Migration + seed
php artisan migrate --seed

# 5. Dev server
php artisan serve
npm run dev
```

## Comandi rapidi

```bash
# Reset completo DB
php artisan migrate:fresh --seed

# Test (Pest)
./vendor/bin/pest

# Smoke test su staging (richiede MySQL reale)
./vendor/bin/pest tests/Feature/SmokeTest.php --no-coverage

# Code style (Pint)
./vendor/bin/pint

# Static analysis (Larastan L6)
./vendor/bin/phpstan analyse

# Queue worker (Step 7+)
php artisan queue:work redis --queue=default

# Scheduler
php artisan schedule:run

# Health check
curl http://localhost:8000/health

# Go-live: inizializza piani abbonamento reali e account gestore
php artisan pilot:init
```

## Struttura

- **app/Livewire/** — Componenti Livewire (Backoffice, Atleta, Admin, Shared)
- **resources/views/livewire/** — Template Blade associate
- **database/migrations/** — Una per tabella, metodo `down()` sempre implementato
- **database/seeders/sql/** — `exercises_seed.sql` (83 esercizi)
- **database/seeders/PilotSeeder.php** — Seed go-live (piani reali + account gestore)
- **tests/Feature, tests/Unit** — Pest (naming descrittivo)
- **config/features.php** — Feature flags (beta trainers, group classes)
- **config/pilot.php** — Piani abbonamento e credenziali gestore per go-live
- **config/backup.php** — Backup automatico con retention (7G → 4W → 3M)
- **.github/workflows/ci.yml** — GitHub Actions (test, pint, phpstan)

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
| Atleta | PWA + App | Vede schede, esegue workout, registra feedback, consulta grafici |
| Trainer | Backoffice | Crea template, assegna mesocicli, monitora, autoregola |
| Gestore | Backoffice | KPI, dati finanziari, staff, listini. Privilegi trainer. |
| Receptionist | Backoffice | Check-in, anagrafica, certificati, abbonamenti. Training in lettura |

## Variabili .env rilevanti

```
# Flare error tracking
FLARE_KEY=

# Feature flags
FEATURE_BETA_TRAINERS=trainer1@email.com,trainer2@email.com
FEATURE_GROUP_CLASSES=false

# Feedback in-app
FEEDBACK_EMAIL=feedback@iron-gym.local

# Go-live
PILOT_MANAGER_EMAIL=gestore@palestra.it
PILOT_MANAGER_PASSWORD=password-sicura

# Push PWA
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
```

## Secrets GitHub Actions

```
STAGING_HOST        # IP/hostname server staging
STAGING_USER        # Utente SSH
STAGING_KEY         # Chiave privata SSH (RSA/ED25519 PEM)
```

---

**Vedi CLAUDE.md per convenzioni di codice e CHANGELOG.md per la storia del progetto.**
