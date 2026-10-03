#!/usr/bin/env sh
set -eu

if [ "${AUREVIA_SKIP_APP_CHECK:-0}" = "1" ]; then
    exec "$@"
fi

if [ ! -f composer.json ]; then
    echo >&2 "Aurevia Health API has not been bootstrapped. Expected apps/api/composer.json."
    exit 1
fi

if [ ! -f vendor/autoload.php ]; then
    echo "Installing Laravel Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ -f artisan ] && { [ ! -f .env ] || ! grep -Eq '^APP_KEY=base64:.+' .env; }; then
    php artisan key:generate --force --no-interaction
fi

exec "$@"
