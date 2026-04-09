#!/bin/sh
set -e

# Create Laravel storage directories
mkdir -p /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

# All containers cache config (env vars are available at runtime)
php artisan config:cache

exec "$@"
