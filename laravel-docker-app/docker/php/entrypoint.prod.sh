#!/bin/sh
set -e

# Ensure storage directories exist with correct permissions
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Copy public files to shared volume for nginx
if [ -d "/var/www/html/public_static" ]; then
    cp -r /var/www/html/public_static/* /var/www/html/public/
fi

# Run migrations if enabled
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Clear and optimize caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM
exec "$@"