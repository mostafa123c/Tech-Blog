#!/bin/sh
set -e

echo "Waiting for MySQL..."

# Wait for MySQL using PHP (uses Laravel's env properly)
until php -r "
\$host = getenv('DB_HOST') ?: 'mysql';
\$port = getenv('DB_PORT') ?: '3306';
try {
    new PDO(\"mysql:host=\$host;port=\$port\", getenv('DB_USERNAME') ?: 'blog_user', getenv('DB_PASSWORD') ?: 'blog_password');
    exit(0);
} catch (PDOException \$e) {
    exit(1);
}
" 2>/dev/null; do
  echo "MySQL not ready, waiting..."
  sleep 2
done

echo "MySQL is up – starting queue worker"
exec php artisan queue:work redis --tries=3 --verbose
