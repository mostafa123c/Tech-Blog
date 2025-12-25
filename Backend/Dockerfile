FROM php:8.2-fpm-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    linux-headers \
    mysql-client \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy entrypoint and helper scripts
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/wait-for-mysql.sh

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
