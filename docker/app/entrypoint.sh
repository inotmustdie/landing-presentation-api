#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p storage/logs \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/app/private \
  bootstrap/cache

chmod -R 775 storage bootstrap/cache

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force --ansi
fi

exec "$@"
