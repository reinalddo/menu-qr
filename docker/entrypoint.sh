#!/bin/sh
# Prepares the data volume, applies migrations and prints the setup or
# recovery code before starting Apache.
set -eu

if [ "${1:-}" = "init-secrets" ]; then
    exec /usr/local/bin/init-secrets
fi

data="${MENUQR_DATA_DIR:-/var/www/data}"
for dir in "$data" "$data/uploads" "$data/setup" "$data/sessions"; do
    mkdir -p "$dir"
    chown www-data:www-data "$dir"
done
chmod 0700 "$data/setup" "$data/sessions"

if [ "${1:-}" = "apache2-foreground" ]; then
    php /var/www/app/bin/migrate.php
    php /var/www/app/bin/boot-codes.php
fi

exec "$@"
