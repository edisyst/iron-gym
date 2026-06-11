# iron-gym

Software di gestione per palestra di bodybuilding e fitness con focus sulla personalizzazione dell'allenamento. Stack: Laravel 13, Livewire 3, AdminLTE 3, MySQL 8, Redis 7.

## Prerequisiti

- PHP 8.3
- Composer 2.x
- Node 20 LTS
- Docker (per MySQL e Redis in dev)

## Quick start

```bash
# 1. Avvia i container (MySQL + Redis)
docker compose up -d

# 2. Copia e configura il file env
cp .env.example .env
php artisan key:generate

# 3. Installa dipendenze
composer install
npm install

# 4. Esegui migration e seed
php artisan migrate --seed

# 5. Avvia il dev server
php artisan serve
npm run dev
```

## Comandi utili

```bash
# Reset completo del DB
php artisan migrate:fresh --seed

# Test
./vendor/bin/pest

# Code style
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```
