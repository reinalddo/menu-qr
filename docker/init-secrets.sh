#!/bin/sh
# Creates the database passwords once. A value set in the environment is used
# on the first run; afterwards the stored file always wins, because MySQL only
# reads its password when the data volume is created.
set -eu
umask 022

dir="${MENUQR_SECRETS_DIR:-/run/menuqr}"
mkdir -p "$dir"

random_secret() {
    head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 32
}

store() {
    name="$1"
    value="$2"
    file="$dir/$name"
    if [ -s "$file" ]; then
        if [ -n "$value" ] && [ "$value" != "$(cat "$file")" ]; then
            echo "init: $name already exists; the environment value is ignored (MySQL only reads it on the first start)"
        else
            echo "init: $name already exists"
        fi
    elif [ -n "$value" ]; then
        printf '%s' "$value" > "$file"
        echo "init: $name taken from the environment"
    else
        printf '%s' "$(random_secret)" > "$file"
        echo "init: $name generated"
    fi
    chmod 0644 "$file"
}

store db_password "${MENUQR_DB_PASSWORD:-}"
store db_root_password "${MENUQR_DB_ROOT_PASSWORD:-}"
