#!/usr/bin/env bash
set -e

DB_FILE="/var/www/html/database-data/database.sqlite"
HTPASSWD_FILE="/etc/apache2/.htpasswd"
AUTOLOAD_FILE="/var/www/html/vendor/autoload.php"
PAIL_PROVIDER_FILE="/var/www/html/vendor/laravel/pail/src/PailServiceProvider.php"

if [[ ! -f "$DB_FILE" ]]; then
  mkdir -p "$(dirname "$DB_FILE")"
  touch "$DB_FILE"
fi

if [[ ! -f "$AUTOLOAD_FILE" ]]; then
  echo "vendor/autoload.php missing; running composer install"
  cd /var/www/html
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
elif [[ "${APP_ENV:-production}" == "local" && ! -f "$PAIL_PROVIDER_FILE" ]]; then
  echo "laravel/pail missing; running composer install for local dev dependencies"
  cd /var/www/html
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database-data

# Viteのhotファイルが残っているとdev serverを参照してしまうため削除する
rm -f /var/www/html/public/hot

if [[ -n "${BASIC_AUTH_USER:-}" && -n "${BASIC_AUTH_PASSWORD:-}" ]]; then
  htpasswd -bc "$HTPASSWD_FILE" "$BASIC_AUTH_USER" "$BASIC_AUTH_PASSWORD"
  chmod 640 "$HTPASSWD_FILE"
  chown www-data:www-data "$HTPASSWD_FILE"
fi

if [[ $# -gt 0 ]]; then
  exec "$@"
fi

# Laravel Schedulerをcronで毎分実行
echo "* * * * * www-data cd /var/www/html && php artisan schedule:run >> /var/www/html/storage/logs/scheduler.log 2>&1" > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler
cron

exec apache2-foreground
