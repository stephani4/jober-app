#!/bin/sh
set -e

if [ -f /etc/nginx/certs/fullchain.pem ] && [ -f /etc/nginx/certs/privkey.pem ]; then
  if [ -w /etc/nginx/nginx.conf ]; then
    cp /etc/nginx/nginx.ssl.conf /etc/nginx/nginx.conf
  fi
  chmod 600 /etc/nginx/certs/privkey.pem 2>/dev/null || true
fi

cd /var/www/html

if [ ! -f .env ]; then
  php -r 'file_put_contents(".env", preg_replace("/^\xEF\xBB\xBF/", "", file_get_contents(".env.example")));'
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

# Migrations flush Redis cache (Spatie). Supervisord starts Redis later, so boot a
# temporary instance now and hand the port back before supervisord takes over.
echo "Starting Redis for migrations..."
redis-server --bind 127.0.0.1 --protected-mode yes --daemonize yes --save "" --appendonly no --pidfile /run/redis-migrate.pid
i=0
until redis-cli ping >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -ge 30 ]; then
    echo "Redis did not become ready."
    exit 1
  fi
  sleep 1
done

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

if [ -f /run/redis-migrate.pid ]; then
  redis-cli shutdown nosave >/dev/null 2>&1 || true
  i=0
  while redis-cli ping >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 20 ]; then
      break
    fi
    sleep 1
  done
fi

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/jober.conf
