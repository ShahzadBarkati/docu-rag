#!/bin/sh
set -e

role="${APP_ROLE:-web}"

if [ "$role" = "web" ]; then
    echo "[entrypoint] running database migrations"
    php artisan migrate --force --no-interaction
    echo "[entrypoint] starting nginx + php-fpm + queue worker"
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf
fi

if [ "$role" = "worker" ]; then
    echo "[entrypoint] starting queue worker"
    exec php artisan queue:work --tries=3 --timeout=1200 --sleep=1 --memory=2048
fi

echo "[entrypoint] unknown APP_ROLE='$role' (expected 'web' or 'worker')" >&2
exit 1