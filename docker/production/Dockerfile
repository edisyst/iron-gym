# Stage 1 — build asset front-end
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# Stage 2 — dipendenze PHP senza dev
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 3 — runtime
FROM dunglas/frankenphp:1-php8.3-bookworm

# Estensioni richieste dallo stack
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
