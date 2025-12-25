#!/bin/sh
set -e

# Install composer dependencies if vendor folder is missing
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --optimize-autoloader --no-interaction
fi

mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public/users_images
mkdir -p /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

if [ ! -L "/var/www/html/public/storage" ]; then
    php artisan storage:link 2>/dev/null || true
fi

php artisan key:generate --force 2>/dev/null || true
php artisan jwt:secret --force 2>/dev/null || true

echo "Starting PHP-FPM..."
exec php-fpm
