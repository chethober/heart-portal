#!/bin/sh
set -e

# Only the PHP-FPM container prepares the app; one-off `run` commands skip straight to exec.
if [ "$1" = "php-fpm" ]; then
    su -s /bin/sh www-data -c '
        php artisan migrate --force &&
        php artisan db:seed --class=RolesAndPermissionsSeeder --force &&
        php artisan optimize
    '
fi

exec docker-php-entrypoint "$@"
