FROM php:8.4-cli

# System dependencies
RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libzip-dev \
        libpq-dev \
    && docker-php-ext-install pdo pdo_mysql zip bcmath \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

# Application code
COPY . .
RUN composer dump-autoload --optimize

RUN chmod +x docker/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
