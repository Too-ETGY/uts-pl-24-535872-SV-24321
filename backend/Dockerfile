# ---- base: shared PHP CLI image with extensions for dev/test ----
FROM php:8.5.12-cli-alpine AS base

RUN apk add --no-cache \
        $PHPIZE_DEPS \
        oniguruma-dev \
        libzip-dev \
        curl \
        linux-headers \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath \
    && apk del $PHPIZE_DEPS linux-headers \
    && rm -rf /var/cache/apk/*

# Pin Composer >= 2.10.3
COPY --from=composer:2.10 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- dev: includes dev/test dependencies, keeps source mounted ----
FROM base AS dev

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --prefer-dist || true

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ---- deps: install production dependencies ----
FROM base AS deps

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ---- production: FrankenPHP server (optional bonus) ----
FROM dunglas/frankenphp:1.4-php8.4-alpine AS production

RUN install-php-extensions pdo_mysql bcmath zip

WORKDIR /var/www/html

COPY --from=composer:2.10 /usr/bin/composer /usr/bin/composer
COPY --from=deps /var/www/html/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize \
    && php artisan config:clear

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force && frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile"]
