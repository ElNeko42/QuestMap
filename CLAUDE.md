# QuestMap — Backend (Laravel 12)

Geolocated AI-generated quests app (Pokémon-Go-style, but useful: tourism, urban
adventures). This repo is the **API-only Laravel backend**. The mobile frontend
(Expo) and the n8n workflows are separate and integrate over HTTP.

## Product in one paragraph

Users see a map of nearby quest pins. They physically approach (≈50 m geofence),
check in and/or upload a photo. A vision AI (running in n8n, external) validates
the photo asynchronously and awards XP. There are levels, a weekly per-city
leaderboard, and themed routes. Quests are produced by an external n8n workflow
that POSTs to internal, HMAC-signed endpoints.

## Stack

- Laravel 12, PHP 8.4 (spec asks 8.3+), API-only.
- PostgreSQL 16 + **PostGIS** (`postgis/postgis:16-3.4`). All spatial columns are
  `geography(Point, 4326)` with GIST indexes.
- Laravel Sanctum (token auth for the mobile app).
- Queues on the `database` driver (swap to Redis later).
- Local disk storage via Flysystem (`photos` disk), ready for S3/R2.
- Pest for tests.

Code and comments in **English**; API error messages may be in **Spanish**.

## Dev environment (Docker Compose)

This is a shared VPS with many services already on the common ports (host nginx on
80/443, other Postgres on 5432/5433, n8n on 5678, qdrant 6333/6334, redis 6379,
ollama 11434). QuestMap therefore runs its **own** isolated stack on non-colliding
host ports:

| Service   | Container            | Host port      | Notes                              |
|-----------|----------------------|----------------|------------------------------------|
| nginx     | `questmap_nginx`     | `8090` → 80    | API entrypoint                     |
| php-fpm   | `questmap_app`       | —              | Laravel app + queue worker         |
| postgis   | `questmap_postgis`   | `5434` → 5432  | `postgis/postgis:16-3.4`           |
| mailpit   | `questmap_mailpit`   | `8026` UI, `1026` SMTP | dev mail                   |

The app reaches the host's existing **n8n** at `http://host.docker.internal:5678`
(configured via `extra_hosts: host-gateway`). Nothing in the shared Postgres /
qdrant is touched — QuestMap has its own database.

### Everyday commands

```bash
# from repo root (/var/www/QuestMap)
docker compose up -d                 # start the stack
docker compose ps                    # status
docker compose logs -f app           # app logs

# artisan / composer (run inside the app container)
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test          # Pest
docker compose exec app composer install

# queue worker runs as its own container (questmap_queue); to run ad-hoc:
docker compose exec app php artisan queue:work --stop-when-empty

# scheduler / expire command
docker compose exec app php artisan quests:expire
```

API base URL in dev: `http://localhost:8090/api` (or the VPS IP:8090).

## Architecture & conventions

- **Thin controllers**, business logic in `app/Services`:
  - `QuestService` — nearby query (PostGIS), quest lifecycle.
  - `CompletionService` — checkin/submit, geofence check, completion records.
  - `XpService` — award XP, recompute level, exhaust quests (pessimistic lock).
  - `AntiCheatService` — implied-speed check against location history.
  - `HmacService` — sign/verify internal payloads (used by job + middleware).
- **Form Requests** for validation, **API Resources** for responses.
- Native PHP **enums** for statuses (`app/Enums`).
- Business constants in **`config/questmap.php`** (default radius, confidence
  thresholds, max speed, rate limits).
- Geography columns are **not** managed by the schema builder: created with
  `DB::statement` in migrations, and cast in models via `App\Casts\AsGeographyPoint`
  which converts between `['lat'=>..,'lng'=>..]` and PostGIS WKT/WKB.

### Key business rules

- **Geofence is server-side**: checkin/submit validate with
  `ST_DWithin(quest.location, point, quest.geofence_radius_m)`; else `422`.
- **Anti-cheat**: on each received location, compare to last history point; if
  implied speed > `questmap.max_speed_kmh` (150), reject `422` and log.
- **Photo validation flow** (fire-and-forget + callback):
  `POST /submit` → store photo → `pending` completion → `NotifyValidationWorkflowJob`
  POSTs to n8n (`N8N_VALIDATION_WEBHOOK_URL`) with `completion_id`, a temporary
  signed photo URL, `validation_prompt`, `callback_url`; HMAC in `X-Signature`.
  n8n later calls the callback: `confidence ≥ 0.85` → approved + XP; `0.5–0.85` →
  `manual_review`; `< 0.5` → rejected. Callback is **idempotent** (non-pending →
  200 no-op).
- **XP/levels**: `level = floor(sqrt(xp / 100)) + 1`. Awarding increments the
  quest's `completions_count` and marks it `exhausted` at `max_completions`, all
  in a transaction with a pessimistic lock on the quest.
- **Internal HMAC middleware**: `X-Signature = HMAC-SHA256(raw_body, INTERNAL_API_SECRET)`,
  timing-safe compare, reject `X-Timestamp` skew > 5 min (replay protection).
- **Leaderboard**: aggregate over approved completions in the period, cached 5 min.

## API surface

Public (Sanctum): `POST /api/auth/{register,login,logout}`, `GET /api/me`,
`POST /api/me/location`, `GET /api/quests/nearby`, `GET /api/quests/{quest}`,
`POST /api/quests/{quest}/checkin`, `POST /api/quests/{quest}/submit`,
`GET /api/me/completions`, `GET /api/leaderboard`, `GET /api/routes`,
`GET /api/routes/{route}`.

Internal (HMAC): `POST /api/internal/quests/batch`,
`POST /api/internal/completions/{completion}/validation-callback`.

## Seeded data

Tenant **A Coruña** (43.3623, -8.4115, radius 10 km), 20 realistic quests, 5 test
users with varied XP, 2 themed routes. See `database/seeders`.
