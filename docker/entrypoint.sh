#!/bin/sh
set -eu

: "${APP_KEY:?APP_KEY must be configured in the DockHosting environment}"

if [ "${DB_CONNECTION:-}" != "mysql" ]; then
    echo "DB_CONNECTION must be set to mysql for this production image." >&2
    exit 1
fi

DATABASE_URL="${DB_URL:-${DATABASE_URL:-}}"
if [ -n "$DATABASE_URL" ]; then
    case "$DATABASE_URL" in
        mysql://*) ;;
        *)
            echo "The production database URL must use the mysql:// scheme." >&2
            exit 1
            ;;
    esac
elif [ -z "${DB_HOST:-}" ] || [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ]; then
    echo "Configure DATABASE_URL or DB_HOST, DB_DATABASE, and DB_USERNAME for MySQL." >&2
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
