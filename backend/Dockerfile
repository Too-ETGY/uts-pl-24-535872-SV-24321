# ---- base: shared PHP image with extensions ----
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

# ---- deps: install production dependencies ----
FROM base AS deps

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ---- production ----
FROM base AS production

COPY --from=deps /var/www/html/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize \
    && php artisan config:clear

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ---- dev: includes dev/test dependencies, keeps source mounted ----
FROM base AS dev

# Dev target installs ALL dependencies (including require-dev)
# Source is expected to be bind-mounted at /var/www/html
COPY composer.json composer.lock* ./
RUN composer install --no-scripts --prefer-dist || true

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
