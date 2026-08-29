# Jober

Доска заданий / объявлений для исполнителей. Заказы могут публиковать заказчики и исполнители. Приложение работает в режиме realtime.

## UI

Default color palette is **light**, with optional **dark** theme.

```bash
docker compose up -d --build
```

| Service     | URL                    |
|-------------|------------------------|
| PWA (Vite)  | http://localhost:5173  |
| Admin (Vite)| http://localhost:5174  |
| API         | http://localhost:8000  |
| Centrifugo  | http://localhost:8001  |
| Redis       | localhost:6379         |
| PostgreSQL  | localhost:5432         |

DB: `jober` / user `jober` / password `jober`

Админка: отдельный SPA, логин из таблицы `admins` (сидер: `admin@jober.local` / `password`).

```bash
docker compose logs -f
docker compose down
```

## Деплой (PaaS / Timeweb App Platform)

Платформа собирает образ из **корня репозитория** (`Dockerfile`). Локальная разработка по-прежнему через `docker compose`.

Тип приложения: **Dockerfile**. Порт контейнера: **8080**.

После деплоя:

| URL | Что |
|-----|-----|
| `/` | PWA |
| `/admin/` | Админка |
| `/api` | Laravel API |
| `/up` | Healthcheck |

Нужна внешняя PostgreSQL. Задайте в панели:

- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `APP_KEY` (сгенерировать: `php artisan key:generate --show`)
- `APP_URL`, `FRONTEND_URL` — публичный `https://…` домен
- `ADMIN_URL` — тот же домен с суффиксом `/admin`
- `VITE_VK_MAPS_API_KEY` — build arg, иначе карты в PWA будут без ключа

Админка после сидера: `admin@jober.local` / `password` (смените `ADMIN_PASSWORD`).
