# Production image for PaaS that builds from the repo root (`docker build .`).
# Serves PWA at /, admin at /admin/, API at /api, realtime at /connection/.

FROM node:22-bookworm AS pwa-build
WORKDIR /build
COPY pwa/package.json pwa/package-lock.json ./
RUN npm ci
COPY pwa/ ./
ARG VITE_VK_MAPS_API_KEY=
ENV VITE_VK_MAPS_API_KEY=$VITE_VK_MAPS_API_KEY
RUN npm run build

FROM node:22-bookworm AS admin-build
WORKDIR /build
COPY admin/package.json admin/package-lock.json ./
RUN npm ci
COPY admin/ ./
ENV VITE_BASE=/admin/
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction
COPY backend/ ./
RUN composer dump-autoload --optimize --no-interaction

FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    nginx \
    supervisor \
    redis-server \
    gettext-base \
  && docker-php-ext-install pdo_pgsql pgsql pcntl posix zip opcache \
  && rm -rf /var/lib/apt/lists/*

COPY --from=centrifugo/centrifugo:v6 /usr/local/bin/centrifugo /usr/local/bin/centrifugo
RUN chmod +x /usr/local/bin/centrifugo

COPY deploy/php.ini /usr/local/etc/php/conf.d/jober.ini
COPY deploy/zz-clear-env.conf /usr/local/etc/php-fpm.d/zz-clear-env.conf
COPY deploy/nginx.conf /etc/nginx/nginx.conf
COPY deploy/supervisord.conf /etc/supervisor/conf.d/jober.conf
COPY deploy/centrifugo.json.template /etc/centrifugo/config.json.template
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
  && mkdir -p /run /var/lib/nginx /var/www/pwa /var/www/admin /etc/centrifugo

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=pwa-build /build/dist /var/www/pwa
COPY --from=admin-build /build/dist /var/www/admin

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql \
    DB_PORT=5432 \
    REDIS_CLIENT=predis \
    REDIS_HOST=127.0.0.1 \
    REDIS_PORT=6379 \
    CACHE_STORE=redis \
    SESSION_DRIVER=redis \
    QUEUE_CONNECTION=redis \
    BROADCAST_CONNECTION=log \
    CENTRIFUGO_URL=http://127.0.0.1:8000 \
    CENTRIFUGO_API_KEY=jober-dev-api-key-change-me \
    CENTRIFUGO_TOKEN_HMAC_SECRET=jober-dev-client-secret-change-me \
    CENTRIFUGO_TOKEN_TTL=3600 \
    CENTRIFUGO_PROXY_SECRET=jober-dev-proxy-secret

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
