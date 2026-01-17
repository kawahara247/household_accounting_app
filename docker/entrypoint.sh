#!/usr/bin/env bash
set -e

DB_FILE="/var/www/html/database-data/database.sqlite"
HTPASSWD_FILE="/etc/apache2/.htpasswd"

if [[ ! -f "$DB_FILE" ]]; then
  mkdir -p "$(dirname "$DB_FILE")"
  touch "$DB_FILE"
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database-data

if [[ -n "${BASIC_AUTH_USER:-}" && -n "${BASIC_AUTH_PASSWORD:-}" ]]; then
  htpasswd -bc "$HTPASSWD_FILE" "$BASIC_AUTH_USER" "$BASIC_AUTH_PASSWORD"
  chmod 640 "$HTPASSWD_FILE"
  chown www-data:www-data "$HTPASSWD_FILE"
fi

if [[ $# -gt 0 ]]; then
  exec "$@"
fi

exec apache2-foreground
