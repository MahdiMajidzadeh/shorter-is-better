# syntax=docker/dockerfile:1

# FrankenPHP bundles PHP 8.4 and a Caddy web server in a single process, so the
# runtime image needs no nginx, php-fpm or supervisor.

# ---------------------------------------------------------------------------
# base — the PHP runtime every other stage builds on. Keeping extensions here
# means the vendor stage resolves the exact same platform requirements that
# production will run with.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine AS base

WORKDIR /app

# bcmath/intl: string & URL handling in the short-url stack
# pcntl: graceful shutdown for queue workers and the scheduler
# pdo_mysql, redis: datastores
# opcache, zip: performance and composer archives
RUN install-php-extensions \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        redis \
        zip

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# ---------------------------------------------------------------------------
# vendor — Composer install. Manifests are copied first so that a code-only
# change reuses the (slow) package download layer.
# ---------------------------------------------------------------------------
FROM base AS vendor

RUN install-php-extensions @composer

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# Autoloader generation needs the application classes present.
COPY app ./app
COPY bootstrap ./bootstrap
COPY database ./database
COPY artisan ./artisan

RUN composer dump-autoload --no-dev --optimize --no-scripts --no-interaction

# ---------------------------------------------------------------------------
# runtime — the published image.
# ---------------------------------------------------------------------------
FROM base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SERVER_NAME=":8080" \
    CONTAINER_ROLE=app

# Application code stays root-owned and read-only to the runtime user; only the
# few paths below are made writable.
COPY . .
COPY --from=vendor /app/vendor ./vendor

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Runtime-writable paths, plus Caddy's own state now that we drop privileges.
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /data/caddy \
        /config/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

# `artisan storage:link` at runtime would need a writable public/, so link now.
# Relative target, so it still resolves once /app/storage is a mounted volume.
RUN ln -sfn ../storage/app/public public/storage

USER www-data

EXPOSE 8080

# Plain app-boot probe: it deliberately does not touch the database, so a
# database blip never turns into a container restart loop.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
