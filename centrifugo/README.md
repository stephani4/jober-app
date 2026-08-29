# Centrifugo (Docker)

## Start

```bash
docker compose up -d
```

## URLs

- WebSocket / HTTP API: http://localhost:8001
- Admin UI: http://localhost:8001 (password in `config.json`)

## Stop

```bash
docker compose down
```

Change secrets in `config.json` before any non-local use.
