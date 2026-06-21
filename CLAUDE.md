# iron-gym

Software di gestione per palestra di bodybuilding e fitness con focus sulla personalizzazione dell'allenamento.

## Stack tecnico

- **Backend:** PHP 8.3, Laravel 11.x
- **Frontend backoffice:** Livewire 3 + Alpine.js, tema AdminLTE 3.x
- **App atleta (futura):** PWA con Alpine.js
- **Database:** MySQL 8.0 (database: `iron_gym`)
- **Cache / queue:** Redis 7
- **Storage:** filesystem locale (MinIO/S3 in step successivi)
- **Dev server:** `php artisan serve` (no Nginx in dev)
- **Asset build:** Vite + Node 20 LTS
- **Auth:** Laravel Breeze (stack Livewire)
- **Permissions:** spatie/laravel-permission
- **Static analysis:** Larastan livello 6
- **Code style:** Laravel Pint
- **Test:** Pest
- **Container:** Docker Compose per dev (app, db, redis, node)
- **CI/CD:** GitHub Actions (`.github/workflows/ci.yml`)
- **Repo:** GitHub.com
- **Branch model:** `main` (produzione), `develop` (integrazione), feature branches da `develop`

## Convenzioni

- **Lingua:** rispondere sempre in italiano, tono informale. Termini tecnici in inglese.
- **Commenti nel codice:** in italiano (termini tecnici in inglese).
- **Naming DB:** tabelle plurali snake_case, FK con suffisso `_id`, soft delete solo dove serve audit.
- **Naming PHP:** PSR-12, classi PascalCase, metodi camelCase, costanti UPPER_SNAKE.
- **Migration:** una per tabella, naming Laravel standard (`create_<table>_table`), `down()` sempre implementato.
- **Seeder:** uno per dominio (es. `MovementPatternSeeder`, `MuscleSeeder`, `EquipmentSeeder`, `ExerciseSeeder`). Il seeder esercizi carica il file SQL `database/seeders/sql/exercises_seed.sql`.
- **Livewire components:** in `app/Livewire/<Area>/<NomeComponent>` (es. `app/Livewire/Backoffice/Exercises/ExerciseList`).
- **Blade views:** in `resources/views/livewire/<area>/...` per componenti, `resources/views/<area>/...` per pagine.
- **Test:** Pest, in `tests/Feature` e `tests/Unit`, naming descrittivo (`it('crea un mesociclo con n settimane', ...)`).

## Documentazione di dominio (fonte di verità)

Prima di scrivere codice che tocca il dominio training, leggere SEMPRE i documenti seguenti:

- @docs/domain/step-0-discovery.md — modello di dominio completo (glossario BB, personas, tassonomia, ERD, schema SQL preliminare, regole di progressione). Database target `iron_gym`, MySQL 8.
- @docs/domain/exercises-catalog.md — catalogo esercizi seed (83 esercizi, 26 muscoli, 14 equipment, 27 movement patterns) con seed SQL pronto.
- @docs/domain/glossary.md — glossario di dominio (terminologia BB, tecniche speciali, personas, tassonomia esercizi). Vista derivata dallo step-0 per riferimento rapido.

Le decisioni architetturali prese sono definitive salvo discussione esplicita. In particolare:

- Single-tenant (una palestra, niente `gym_id`).
- `movement_pattern` è tabella di lookup (`movement_patterns`) con colonna `category` (`compound_pattern` / `joint_action`).
- `muscles` ed `equipment` sono tabelle di lookup.
- Altri ENUM (`mechanic`, `plane`, `laterality`, `skill_level`, `measurement_type`, `goal`, `periodization_model`, `technique_type`, `group_type`) restano ENUM nativi MySQL.
- Mesociclo è "snapshottato" all'istanziamento da template (modifiche al template non si propagano).
- Esercizi unilaterali: un Set = un effort per coppia di lati (no granularità DX/SX nell'MVP).
- Tempo dell'esecuzione: campo `tempo VARCHAR(7)` (es. `3-1-1-0`) presente fin dallo Step 0.
- Feedback post-sessione su scala 0-3.
- Lingua applicazione: solo italiano nell'MVP.

## Step di sviluppo

- **Step 0 — Discovery (✅ completato):** dominio, ERD, schema SQL preliminare, catalogo esercizi. Vedi `docs/domain/`.
- **Step 1 — Skeleton + core gestionale minimo (✅ completato):** struttura Laravel, Docker compose, CI GitHub Actions, migration dello schema training-core, seed catalogo, autenticazione e ruoli, anagrafica tesserati base.
- **Step 2 — Libreria esercizi e workout builder (✅ completato).**
- **Step 3 — App atleta v1 e workout logging (✅ completato).**
- **Step 4 — Periodizzazione e autoregolazione (volume landmarks, deload) (✅ completato).**
- **Step 5 — Tracking corporeo e analytics (✅ completato).**
- **Step 6 — Prenotazioni e calendario (✅ completato).**
- **Step 7 — CRM, comunicazione, notifiche (✅ completato).**
- **Step 8 — Reportistica gestore e finanza (✅ completato).**
- **Step 9 — Hardening, DevOps, deployment (✅ completato).**
- **Step 10 — Pilota in palestra reale e iterazione (PROSSIMO).**

## Indicazioni operative per Claude Code

- **Search-first:** prima di rispondere su librerie, versioni, comandi, best practice o tool, cercare sempre online. Preferire risposte aggiornate.
- **Ambiguità:** se una richiesta è incompleta o ambigua, chiedere chiarimenti prima di procedere. Niente assunzioni silenziose.
- **Codice:** mantenere esattamente formattazione, spaziatura e indentazione dell'utente. Per blocchi brevi mostrare il file/blocco completo; per blocchi lunghi solo le parti modificate con contesto minimo sufficiente.
- **Spiegazioni:** brevi, dirette, in prosa continua. Niente liste puntate salvo richiesta esplicita. Non approfondire oltre quanto chiesto.
- **Proattività:** non suggerire approcci alternativi o best practice se non richiesti esplicitamente.
- **Rischi:** segnalare side effect solo se critici (sicurezza, perdita irreversibile di dati). Omettere avvertenze minori.
- **Niente emoji.**

## Secrets GitHub Actions richiesti

Per il job `deploy-staging`:
- `STAGING_HOST` — IP/hostname del server di staging
- `STAGING_USER` — utente SSH
- `STAGING_KEY` — chiave privata SSH (RSA/ED25519, PEM format)

## Comandi utili (dopo lo Step 1)

```bash
# Avvio ambiente dev
docker compose up -d
php artisan serve
npm run dev

# Worker coda Redis (Step 7+)
php artisan queue:work redis --queue=default

# Scheduler (sviluppo locale)
php artisan schedule:run

# Migrazioni e seed
php artisan migrate:fresh --seed

# Test e qualità codice
./vendor/bin/pest
./vendor/bin/pint
./vendor/bin/phpstan analyse

# Reset DB di dev
php artisan migrate:fresh --seed
```

## Operazioni di manutenzione

```bash
# Backup manuale immediato
php artisan backup:run

# Pulizia backup vecchi (segue la retention config)
php artisan backup:clean

# Lista backup esistenti con stato salute
php artisan backup:list
php artisan backup:monitor

# Flush cache Redis (tutti i tag)
php artisan cache:clear

# Flush cache per tag specifico (da tinker)
# Cache::tags(['kpi'])->flush();
# Cache::tags(['exercises'])->flush();

# Health check manuale
curl http://localhost:8000/health

# Genera icone PWA da resources/images/icon.png
php artisan pwa:generate-icons

# Build produzione asset (minified, con chunk separati)
npm run build

# Ottimizzazione produzione Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache   # se si usa Blade Icons

# Restart queue worker dopo deploy
php artisan queue:restart

# Telescope (solo local): accesso via /telescope
# Abilitato tramite TELESCOPE_ENABLED=true in .env
```

### Struttura backup

I backup vengono salvati in `storage/app/iron-gym/` (disco `local`).
In produzione/staging impostare `BACKUP_DISK=s3` e configurare `config/filesystems.php` con il disco S3.
Il fallimento del backup invia una mail a `BACKUP_NOTIFY_EMAIL`.

Retention: 7 giornalieri → 4 settimanali → 3 mensili.
