# Phase 10 – Container image for Laravel backend. Build once, deploy to staging then production.
# Build: docker build -t razergold-backend .
# Run (example): docker run -p 8000:8000 --env-file .env razergold-backend

FROM php:8.2-cli-alpine AS base

RUN apk add --no-cache \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    sqlite-libs \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        opcache \
        pdo_sqlite \
        zip \
        xml \
        mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# Permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Production config
ENV APP_ENV=production
ENV APP_DEBUG=false

# Expose port (artisan serve or Octane)
EXPOSE 8000

# Default: serve with Octane if available, else artisan serve
CMD ["sh", "-c", "php artisan config:cache && (php artisan octane:start --host=0.0.0.0 --port=8000 2>/dev/null || php artisan serve --host=0.0.0.0 --port=8000)"]
