#!/bin/sh
set -eu

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    until php -r '$host = getenv("DB_HOST"); $port = (int) getenv("DB_PORT"); $connection = @fsockopen($host, $port, $errorCode, $errorMessage, 2); exit($connection ? 0 : 1);'; do
        echo "Aguardando o banco de dados externo..."
        sleep 2
    done

    php artisan migrate --force --no-interaction
fi

php artisan storage:link --force
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

php-fpm -D
exec nginx -g 'daemon off;'
