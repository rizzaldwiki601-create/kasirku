#!/bin/sh
set -eu

: "${APP_KEY:?APP_KEY must be configured in the Render environment}"

if [ "${DB_CONNECTION:-pgsql}" != "pgsql" ]; then
    echo "DB_CONNECTION must be set to pgsql for this production image." >&2
    exit 1
fi

PORT="${PORT:-10000}"
case "$PORT" in
    ''|*[!0-9]*)
        echo "PORT must be a numeric TCP port." >&2
        exit 1
        ;;
esac

envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

su --preserve-environment --shell /bin/sh www-data -c \
    'php artisan config:cache && php artisan view:cache'

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
