#!/bin/bash
set -e

# Create Laravel storage directories
mkdir -p /app/storage/{app/public,framework/{cache/data,sessions,views},logs} \
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
