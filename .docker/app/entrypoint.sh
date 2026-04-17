#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

if [ "$(id -u)" -eq 0 ]; then
  chown -R www-data:www-data storage bootstrap/cache || true
  chmod -R ug+rwX storage bootstrap/cache || true
fi

if [ ! -f ".env" ] && [ -f ".env.example" ]; then
  cp .env.example .env
fi

if [ ! -d "vendor" ]; then
  composer install --no-interaction
fi

php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true

exec "$@"

