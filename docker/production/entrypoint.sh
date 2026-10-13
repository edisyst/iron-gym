#!/bin/sh
set -e

# Attende che MySQL accetti connessioni prima di qualsiasi comando artisan
until php artisan db:monitor --max=1 >/dev/null 2>&1; do
    echo "In attesa del database..."
    sleep 2
done

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force
    php artisan storage:link || true
fi

# Cache obbligatorie in un container immutabile
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

exec "$@"
