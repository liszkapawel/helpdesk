#!/bin/sh
set -e

echo "==> Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Generating JWT keys..."
if [ ! -f config/jwt/private.pem ]; then
    php bin/console lexik:jwt:generate-keypair
    echo "    Keys generated."
else
    echo "    Keys already exist, skipping."
fi

echo "==> Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "==> Clearing cache..."
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

echo "==> Ready!"
exec "$@"
