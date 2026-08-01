#!/usr/bin/env bash
set -e

# Ensure an environment file exists
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Sync .env with the container environment. Laravel's `artisan serve` propagates
# .env file values to its web server, so these must match the Docker network
# (e.g. DB_HOST=db, not 127.0.0.1) for HTTP requests to reach the database.
set_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

set_env APP_URL "${APP_URL:-http://localhost:8000}"
set_env DB_CONNECTION "${DB_CONNECTION:-mysql}"
set_env DB_HOST "${DB_HOST:-db}"
set_env DB_PORT "${DB_PORT:-3306}"
set_env DB_DATABASE "${DB_DATABASE:-taskflow}"
set_env DB_USERNAME "${DB_USERNAME:-taskflow}"
set_env DB_PASSWORD "${DB_PASSWORD:-secret}"
set_env QUEUE_CONNECTION "${QUEUE_CONNECTION:-database}"
set_env MAIL_MAILER "${MAIL_MAILER:-log}"

# Generate an application key if one is not set
if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

# Wait until the database actually accepts TCP connections (the MySQL health
# check can pass on its socket before the network listener is ready).
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" >/dev/null 2>&1; do
    sleep 2
done
echo "Database is ready."

# Clear any stale cached config, then run migrations and seed sample data
php artisan config:clear
php artisan migrate --seed --force

# Start the application
exec php artisan serve --host=0.0.0.0 --port=8000
