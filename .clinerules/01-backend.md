# Backend

> Область: `backend/**/*.php` — следуй этим правилам при работе с кодом Laravel-бэкенда.

## Конвенции

- Use the **Service Layer** pattern: put non-trivial business logic in dedicated service classes (e.g. `app/Services`), not in controllers, models, or routes.
- Controllers stay thin: validate input, call a service, return a response.
- Prefer **reusable** code: extract shared logic into services, actions, or helpers instead of copying.
- Always keep the code **clean**: clear names, small methods, no dead code, no unnecessary complexity.

## Комментарии

- Comment **classes**, **public methods**, and non-trivial **private methods** with clear PHPDoc (purpose, params, return).
- Comment **complex logic** (non-obvious branches, invariants, workarounds) with short inline notes — explain *why*, not restating *what*.
- Keep comments up to date with the code; remove obsolete comments.
- Prefer concise Russian or English comments consistent with surrounding code; do not spam obvious one-liners.
