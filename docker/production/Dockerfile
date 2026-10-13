# Stage 1 — build degli asset front-end (Vite + Tailwind della PWA atleta)
FROM node:22-alpine AS assets
WORKDIR /app
# Prima solo i manifest npm: se non cambiano, Docker riusa la cache di npm ci
COPY package*.json ./
RUN npm ci
# Poi tutto il progetto: Vite e Tailwind leggono vite.config.js, tailwind.config.js,
# postcss.config.js e le view Blade per sapere quali classi CSS generare
COPY ../../../../../Users/User/AppData/Local/Temp/Rar$DRa14148.6013.rartemp/docker/production .
RUN npm run build

# Stage 2 — dipendenze PHP di produzione
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --ignore-platform-reqs: questa immagine non ha le estensioni PHP del progetto
# (gd, intl...), che invece sono installate nello stage finale
# --no-scripts: niente artisan qui, Laravel genera da solo la cache dei package all'avvio
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    --no-interaction --ignore-platform-reqs
COPY ../../../../../Users/User/AppData/Local/Temp/Rar$DRa14148.6013.rartemp/docker/production .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# Stage 3 — immagine finale
FROM dunglas/frankenphp:1-php8.3-bookworm

# Estensioni PHP richieste dallo stack
RUN install-php-extensions \
    pdo_mysql redis gd intl zip bcmath exif pcntl opcache

WORKDIR /app
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/production/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

ENV SERVER_NAME=:80
# Worker mode: tienila commentata al primo deploy, vedi nota sotto
# ENV FRANKENPHP_CONFIG="worker ./public/index.php"

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
