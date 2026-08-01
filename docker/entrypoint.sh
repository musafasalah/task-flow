#!/usr/bin/env bash
set -e

# Ensure an environment file exists
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate an application key if one is not set
if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

# Run migrations and seed sample data
php artisan migrate --seed --force

# Cache configuration for performance
php artisan config:clear

# Start the application
exec php artisan serve --host=0.0.0.0 --port=8000
