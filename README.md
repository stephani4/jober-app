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
