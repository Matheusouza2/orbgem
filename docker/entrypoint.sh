#!/bin/sh
set -eu

mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
chmod -R a+rwX storage bootstrap/cache

if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/composer/installed.php ]; then
    composer install --no-interaction --no-progress --prefer-dist
fi

if [ -z "${APP_KEY:-}" ]; then
    if ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force --no-interaction
    fi

    APP_KEY="$(sed -n 's/^APP_KEY=//p' .env | head -n 1)"
    export APP_KEY
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    until php -r '$host = getenv("DB_HOST"); $port = (int) getenv("DB_PORT"); $connection = @fsockopen($host, $port, $errorCode, $errorMessage, 2); exit($connection ? 0 : 1);'; do
        echo "Aguardando o banco de dados..."
        sleep 2
    done

    php artisan migrate --force --no-interaction
fi

php artisan storage:link --force

if [ ! -x node_modules/.bin/vite ]; then
    npm ci
fi

php-fpm -D
nginx

cleanup() {
    kill "${vite_pid:-}" 2>/dev/null || true
    nginx -s quit 2>/dev/null || true
}

trap cleanup INT TERM EXIT

npm run dev -- --host 0.0.0.0 &
vite_pid=$!

wait "$vite_pid"
