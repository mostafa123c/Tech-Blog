#!/bin/sh
set -e

# Install composer dependencies if vendor folder is missing
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --optimize-autoloader --no-interaction
fi

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    php artisan key:generate --force 2>/dev/null || true
fi

# Start PHP-FPM
exec php-fpm
