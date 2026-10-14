#!/bin/sh
set -e

# Attende che MySQL accetti connessioni prima di qualsiasi comando artisan.
# Se il comando fallisce stampa l'ultima riga dell'errore: distingue l'attesa
# normale (Connection refused) da un problema vero (password errata, classe mancante)
until OUT=$(php artisan db:monitor --max=1 2>&1); do
    echo "In attesa del database..."
    echo "$OUT" | grep -v '^[[:space:]]*$' | tail -n 1
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
