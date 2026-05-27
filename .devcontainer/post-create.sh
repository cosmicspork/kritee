#!/usr/bin/env bash
set -euo pipefail

sudo supervisord -c /etc/supervisor/supervisord.conf || true

echo "Waiting for Postgres..."
until pg_isready -h 127.0.0.1 -p 5432 -q; do
    sleep 1
done

createdb -h 127.0.0.1 -U ubuntu kritee 2>/dev/null || true

[ -f .env ] || cp .env.example .env

composer install
bun install
php artisan key:generate
php artisan migrate --force
bun run build

echo "Devcontainer ready. Run 'just dev' to start the app."
