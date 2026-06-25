# Installazione e avvio in locale

## Prerequisiti

- PHP 8.3 con estensioni: `pdo_mysql`, `redis`, `gd`, `zip`, `bcmath`, `intl`
- Composer 2.x
- Node 20 LTS
- Docker Desktop (per MySQL 8 e Redis 7)

## 1. Clona il repository

```bash
git clone <repo-url> iron-gym
cd iron-gym
```

## 2. Avvia i container

```bash
docker compose up -d
```

Avvia MySQL 8 su `127.0.0.1:3306` e Redis 7 su `127.0.0.1:6379`.
Il database `iron_gym` viene creato automaticamente senza password per root.

## 3. Installa le dipendenze

```bash
composer install
npm install
```

## 4. Configura l'ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Il file `.env.example` è già configurato per lo stack Docker locale (`DB_HOST=127.0.0.1`,
`DB_USERNAME=root`, `DB_PASSWORD=` vuota, `REDIS_HOST=127.0.0.1`). Nessuna modifica
necessaria per un setup standard.

## 5. Migrazione e seed

```bash
php artisan migrate:fresh --seed
```

Crea tutte le tabelle e carica: catalogo esercizi completo (83 esercizi),
muscoli, equipment, movement pattern, ruoli spatie, utenti di test.

## 6. Avvia i processi

Aprire quattro terminali separati (o usare un process manager come `tmux`):

```bash
# Terminale 1 — server HTTP
php artisan serve

# Terminale 2 — asset Vite con HMR
npm run dev

# Terminale 3 — worker coda Redis
php artisan queue:work redis --queue=default

# Terminale 4 — scheduler
php artisan schedule:work
```

L'app è raggiungibile su `http://localhost:8000`.

## Account di default

Dopo il seed sono disponibili questi utenti:

| Ruolo | Email | Password |
|---|---|---|
| Gestore | admin@admin.admin | admin |
| Trainer | trainer@trainer.trainer | trainer |
| Receptionist | receptionist@receptionist.receptionist | receptionist |

## Verifica installazione

```bash
# Test suite completa
./vendor/bin/pest

# Static analysis
./vendor/bin/phpstan analyse --memory-limit=512M

# Health check
curl http://localhost:8000/health
```

## Note

- **Telescope** è abilitato in locale via `/telescope` (`TELESCOPE_ENABLED=true` nel `.env`).
- **Mail**: in locale il driver è `log` — le email vanno in `storage/logs/laravel.log`.
- **VAPID keys** per Web Push: il `.env.example` contiene chiavi di test già pronte.
  In produzione rigenerarle con il comando indicato nel commento del file.
- Per il go-live reale (piani abbonamento reali + account gestore definitivo):
  `php artisan pilot:init`
