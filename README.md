# iron-gym

Software di gestione per palestra di bodybuilding e fitness con focus sulla personalizzazione dell'allenamento e autoregolazione del volume.

**Stack:** PHP 8.3, Laravel 11, Livewire 3 + Alpine.js, AdminLTE 3, MySQL 8, Redis 7, Vite, Pest, Larastan L6.

**Status:** Step 9 completato. App live con backoffice e PWA atleta. Pronto per Step 10 (pilota in palestra reale).

---

## Documentazione

- **CLAUDE.md** — specifiche di progetto, convenzioni, comandi, secrets GitHub Actions.
- **docs/domain/step-0-discovery.md** — modello di dominio completo (glossario BB, ERD, schema SQL, regole progressione).
- **docs/domain/exercises-catalog.md** — catalogo 83 esercizi, 26 muscoli, 14 equipment, 27 movement patterns con seed SQL.
- **docs/domain/glossary.md** — glossario rapido (terminologia, personas, tassonomia).

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
```

## Struttura

- **app/Livewire/** — Componenti Livewire (Backoffice, Atleta, Admin)
- **resources/views/livewire/** — Template Blade associate
- **database/migrations/** — Una per tabella, metodo `down()` sempre implementato
- **database/seeders/sql/** — `exercises_seed.sql` (83 esercizi)
- **tests/Feature, tests/Unit** — Pest (naming descrittivo)
- **config/backup.php** — Backup automatico con retention (7G → 4W → 3M)
- **.github/workflows/ci.yml** — GitHub Actions (test, pint, phpstan)

## Ruoli e accesso

| Persona | Area | Accesso |
|---|---|---|
| Atleta | PWA + App | Vede schede, esegue workout, registra feedback, consulta grafici |
| Trainer | Backoffice | Crea template, assegna mesocicli, monitora, autoregola |
| Gestore | Backoffice | KPI, dati finanziari, staff, listini. Privilegi trainer. |
| Receptionist | Backoffice | Check-in, anagrafica, certificati, abbonamenti. Training in lettura |

## Secrets GitHub Actions (per deploy staging)

```
STAGING_HOST        # IP/hostname server
STAGING_USER        # Utente SSH
STAGING_KEY         # Chiave privata SSH (RSA/ED25519 PEM)
```

---

**Vedi CLAUDE.md per documentazione completa, step progress, e convenzioni di codice.**
