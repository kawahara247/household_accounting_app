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

# root として artisan が叩かれても www-data グループで書き込み可能になるよう setgid + g+w を付与
# （withoutOverlapping のロックファイルが root 所有になり cron の www-data から書けない事故を防ぐ）
chgrp -R www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R g+w /var/www/html/storage /var/www/html/bootstrap/cache
find /var/www/html/storage /var/www/html/bootstrap/cache -type d -exec chmod g+s {} +

# 起動時に古いスケジューラロックが残っているとフィルタが失敗するため掃除
find /var/www/html/storage/framework/cache/data -mindepth 1 ! -name .gitignore -delete 2>/dev/null || true

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

# cron デーモンは子プロセスへ最小環境（HOME/LOGNAME/PATH/SHELL/PWD）しか渡さないため、
# fly.io machine env (DB_DATABASE, APP_KEY 等) を sourceable な形で退避し、
# cron ジョブから BASH_ENV 経由で読み込ませる
env -0 | while IFS='=' read -r -d '' k v; do
  printf 'export %s=%q\n' "$k" "$v"
done > /etc/container-env.sh
chown root:www-data /etc/container-env.sh
chmod 640 /etc/container-env.sh

# Laravel Schedulerをcronで毎分実行
# php は /usr/local/bin にあるため PATH を明示する
cat > /etc/cron.d/laravel-scheduler <<'EOF'
SHELL=/bin/bash
BASH_ENV=/etc/container-env.sh
PATH=/usr/local/bin:/usr/bin:/bin
* * * * * www-data cd /var/www/html && php artisan schedule:run >> /var/www/html/storage/logs/scheduler.log 2>&1
EOF
chmod 0644 /etc/cron.d/laravel-scheduler
cron

exec apache2-foreground
