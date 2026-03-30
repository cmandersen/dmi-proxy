#!/bin/sh
set -e

# Create Laravel storage directories
mkdir -p /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

# Ensure SQLite database exists
touch /app/database/database.sqlite

# Only the web container runs migrations
if [ "${CONTAINER_ROLE}" = "web" ]; then
    php artisan migrate --force
fi

# All containers cache config (env vars are available at runtime)
php artisan config:cache

exec "$@"
