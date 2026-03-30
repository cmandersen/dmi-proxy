# ===========================================
# Stage 1: Install Composer dependencies
# ===========================================
FROM composer:2 AS deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# ===========================================
# Stage 2: Production image
# ===========================================
FROM dunglas/frankenphp:1-php8.3-alpine AS production

RUN apk add --no-cache curl

RUN install-php-extensions \
    pdo_sqlite \
    opcache \
    pcntl \
    intl \
    mbstring

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.interned_strings_buffer=16'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini

WORKDIR /app

COPY --from=deps /app/vendor /app/vendor
COPY . /app

RUN php artisan route:cache \
    && php artisan view:cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/up || exit 1
