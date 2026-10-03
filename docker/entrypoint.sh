#!/bin/bash
set -Eeuo pipefail

cd /var/www

# Locate MySQL CA certificate
ca_source=""
if [[ -n "${MYSQL_ATTR_SSL_CA:-}" && -f "$MYSQL_ATTR_SSL_CA" && -r "$MYSQL_ATTR_SSL_CA" ]]; then
    ca_source="$MYSQL_ATTR_SSL_CA"
elif [[ -f "/var/www/docker/aiven-ca.pem" && -r "/var/www/docker/aiven-ca.pem" ]]; then
    ca_source="/var/www/docker/aiven-ca.pem"
else
    for candidate in /etc/secrets/ca.pem /etc/secrets/ca.pe /etc/secrets/*; do
        if [[ -f "$candidate" && -r "$candidate" ]] && grep -q "BEGIN CERTIFICATE" "$candidate" 2>/dev/null; then
            ca_source="$candidate"
            break
        fi
    done
fi

if [[ -n "$ca_source" ]]; then
    echo "Using MySQL CA certificate from: $ca_source"
    (
        umask 077
        mkdir -p /run/app-certificates
        chown root:www-data /run/app-certificates
        chmod 750 /run/app-certificates
        cp "$ca_source" /run/app-certificates/mysql-ca.pem
        chown www-data:www-data /run/app-certificates/mysql-ca.pem
        chmod 444 /run/app-certificates/mysql-ca.pem
    )
    export MYSQL_ATTR_SSL_CA=/run/app-certificates/mysql-ca.pem
    su-exec www-data php docker/check-ca.php
elif [[ -n "${MYSQL_ATTR_SSL_CA:-}" ]]; then
    echo "Warning: MYSQL_ATTR_SSL_CA was set but no valid certificate found. Proceeding without SSL CA." >&2
    unset MYSQL_ATTR_SSL_CA
fi

# Allow maintenance commands with: docker run ... IMAGE php artisan ...
if (( $# > 0 )); then
    exec su-exec www-data "$@"
fi

: "${APP_KEY:?Set a persistent APP_KEY before starting the application}"
: "${APP_URL:?Set APP_URL to the public HTTPS address}"

export PORT="${PORT:-10000}"
if [[ ! "$PORT" =~ ^[0-9]{1,5}$ ]] || (( 10#$PORT < 1 || 10#$PORT > 65535 )); then
    echo "PORT must be an integer between 1 and 65535" >&2
    exit 1
fi

# Substitute PORT only; preserve Nginx variables such as $uri and $query_string.
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/public storage/picture bootstrap/cache

# Restore catalog photos that are missing (first boot, or a new persistent disk).
# -n keeps files already on the disk, including pictures uploaded from the admin.
if [[ -d /opt/catalog-pictures ]]; then
    cp -an /opt/catalog-pictures/. storage/picture/ || true
fi

chown -R www-data:www-data storage bootstrap/cache

su-exec www-data php artisan config:cache
su-exec www-data php artisan storage:link --force --no-interaction || true

case "${RUN_MIGRATIONS:-true}" in
    true) su-exec www-data php artisan migrate --force --no-interaction ;;
    false) ;;
    *) echo "RUN_MIGRATIONS must be true or false" >&2; exit 1 ;;
esac

if [[ -n "${RUN_SEEDERS:-}" && "${RUN_SEEDERS}" != "true" && "${RUN_SEEDERS}" != "false" ]]; then
    echo "RUN_SEEDERS must be true or false" >&2
    exit 1
fi

su-exec www-data php artisan route:cache
su-exec www-data php artisan view:cache

nginx -t
php-fpm -t

# Stop the whole container if either server exits, and forward stop signals.
server_pids=()
cleanup() {
    trap - EXIT TERM INT
    if (( ${#server_pids[@]} )); then
        kill -QUIT "${server_pids[@]}" 2>/dev/null || true
        wait "${server_pids[@]}" 2>/dev/null || true
    fi
}
trap cleanup EXIT
trap 'exit 0' TERM INT

php-fpm -F &
server_pids+=("$!")
nginx -g 'daemon off;' &
server_pids+=("$!")

# The port is open now. Seeding stays out of the health check so a slow Aiven
# insert cannot make Render mark the deploy as exited.
echo "Web server is listening; seeding admin user and reviews."
su-exec www-data php artisan db:seed --class=AdminUserSeeder --force --no-interaction || echo "Admin seed failed; continuing." >&2
su-exec www-data php artisan db:seed --class=StorefrontReviewSeeder --force --no-interaction || echo "Review seed failed; continuing." >&2
su-exec www-data php artisan products:fix-sizes --no-interaction || echo "Variant size fix failed; continuing." >&2
su-exec www-data php artisan products:clear-models --no-interaction || echo "3D model cleanup failed; continuing." >&2
if [[ "${RUN_SEEDERS:-false}" == "true" ]]; then
    su-exec www-data php artisan db:seed --force --no-interaction || echo "Database seed failed; continuing." >&2
fi

status=0
wait -n "${server_pids[@]}" || status=$?
echo "A web server exited (status $status); stopping container" >&2
exit 1
