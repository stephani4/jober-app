#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

php artisan package:discover --ansi --no-interaction >/dev/null 2>&1 || true

if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force --no-interaction
fi

if [ -z "$JWT_SECRET" ]; then
  php artisan jwt:secret --force --no-interaction
fi

envsubst '${CENTRIFUGO_TOKEN_HMAC_SECRET} ${CENTRIFUGO_API_KEY} ${CENTRIFUGO_PROXY_SECRET}' \
  < /etc/centrifugo/config.json.template \
  > /etc/centrifugo/config.json

chown -R www-data:www-data storage bootstrap/cache

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
  fwrite(STDERR, \$e->getMessage() . PHP_EOL);
  exit(1);
}
"; do
  i=$((i + 1))
  if [ "$i" -ge 60 ]; then
    echo "Database is unavailable after waiting. Set DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD."
    exit 1
  fi
  sleep 2
done

php artisan migrate --force --no-interaction
php artisan db:seed --class=AdminUserSeeder --force --no-interaction

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/jober.conf
