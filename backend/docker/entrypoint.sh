#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
fi

# Ensure key exists (idempotent if already set)
php artisan key:generate --force --no-interaction >/dev/null 2>&1 || true

# Wait for Postgres
echo "Waiting for database..."
i=0
until php -r "
try {
  new PDO(
    'pgsql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '5432') . ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD')
  );
  exit(0);
} catch (Throwable \$e) {
  exit(1);
}
" 2>/dev/null; do
  i=$((i + 1))
  if [ "$i" -ge 30 ]; then
    echo "Database is unavailable after waiting."
    exit 1
  fi
  sleep 1
done

php artisan migrate --force --no-interaction

exec "$@"
