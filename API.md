# QuestMap API — quick reference & curl examples

Base URL (dev): `http://localhost:8090/api` (from the VPS itself; from elsewhere
use `http://<vps-ip>:8090/api`). All authenticated requests need
`Authorization: Bearer <token>` and `Accept: application/json`.

Seeded test users (password `password`): `alba@questmap.test` (4200 xp),
`brais@…`, `carmela@…`, `diego@…`, `uxia@…` (0 xp).

---

## Auth

```bash
# Register
curl -s -X POST http://localhost:8090/api/auth/register \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"name":"Ada","email":"ada@questmap.test","password":"password123","password_confirmation":"password123"}'

# Login -> returns { user, token }
curl -s -X POST http://localhost:8090/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"alba@questmap.test","password":"password"}'

TOKEN=... # paste the token from the response

# Current user
curl -s http://localhost:8090/api/me -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# Logout (revokes the current token)
curl -s -X POST http://localhost:8090/api/auth/logout -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

## Location (updates last_location + history; runs anti-cheat)

```bash
curl -s -X POST http://localhost:8090/api/me/location \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"lat":43.3853,"lng":-8.4064}'
# First location assigns the user to the containing tenant (A Coruña).
```

## Quests

```bash
# Nearby (ordered by distance, includes dist_m). radius in meters (default 2000).
curl -s "http://localhost:8090/api/quests/nearby?lat=43.3853&lng=-8.4064&radius=2000" \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# Filter by category
curl -s "http://localhost:8090/api/quests/nearby?lat=43.37&lng=-8.40&radius=5000&category=food" \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# Show one quest
curl -s http://localhost:8090/api/quests/1 -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# Check-in (only for validation_type=checkin quests). Geofence enforced server-side.
# Quest 2 = Plaza de María Pita (checkin). Approves + awards XP immediately.
curl -s -X POST http://localhost:8090/api/quests/2/checkin \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"lat":43.3703,"lng":-8.3959}'

# Submit photo (only for validation_type=photo_ai quests). Multipart. Returns 202 pending.
# Quest 1 = Torre de Hércules (photo_ai). Max 20 submits/user/day.
curl -s -X POST http://localhost:8090/api/quests/1/submit \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -F 'lat=43.3853' -F 'lng=-8.4064' -F 'photo=@/path/to/photo.jpg'
```

Geofence failure returns `422 { "error_code": "outside_geofence", "meta": { "distance_m", "required_radius_m" } }`.
Teleport/anti-cheat returns `422 { "error_code": "implausible_speed" }`.

## Progress

```bash
# My completions (paginated)
curl -s http://localhost:8090/api/me/completions -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# Leaderboard: period = week (default) | month | all. Top 50 of the user's tenant.
curl -s "http://localhost:8090/api/leaderboard?period=all" -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

## Routes (themed)

```bash
curl -s "http://localhost:8090/api/routes?theme=history" -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
curl -s http://localhost:8090/api/routes/1 -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'  # includes ordered quests
```

---

## Internal routes (n8n) — HMAC signed

Signature: `X-Signature = HMAC-SHA256("{X-Timestamp}.{raw body}", INTERNAL_API_SECRET)`.
Timestamps older than 5 min are rejected (replay protection). Dev secret:
`INTERNAL_API_SECRET` in `.env` (default `change-me-internal-secret-dev`).

```bash
SECRET='change-me-internal-secret-dev'

# 1) Batch-create quests (dedup by dedup_hash; derived from tenant+title+coords if omitted)
BODY='{"tenant_slug":"a-coruna","quests":[{"title":"Ruta nueva","description":"desc","category":"history","lat":43.371,"lng":-8.40,"validation_type":"checkin","xp_reward":75}]}'
TS=$(date +%s)
SIG=$(printf '%s.%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -X POST http://localhost:8090/api/internal/quests/batch \
  -H 'Content-Type: application/json' -H "X-Timestamp: $TS" -H "X-Signature: $SIG" --data-raw "$BODY"
# -> { "created_count", "skipped_count", "created_ids" }

# 2) Validation callback from the vision AI (idempotent)
#    confidence >= 0.85 -> approved + XP ; 0.5–0.85 -> manual_review ; < 0.5 -> rejected
CID=1   # completion id
BODY='{"valid":true,"confidence":0.95,"reason":"Se ve la Torre de Hércules"}'
TS=$(date +%s)
SIG=$(printf '%s.%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -X POST http://localhost:8090/api/internal/completions/$CID/validation-callback \
  -H 'Content-Type: application/json' -H "X-Timestamp: $TS" -H "X-Signature: $SIG" --data-raw "$BODY"
```

### The async validation flow (how n8n plugs in)

1. `POST /quests/{id}/submit` stores the photo, creates a `pending` completion and
   queues `NotifyValidationWorkflowJob`.
2. The job `POST`s to `N8N_VALIDATION_WEBHOOK_URL` (default
   `http://host.docker.internal:5678/webhook/questmap-validate`) with
   `{ completion_id, quest_id, photo_url (temporary signed), validation_prompt, callback_url }`,
   signed with `N8N_WEBHOOK_SECRET` in `X-Signature`. n8n should reply `200`
   immediately.
3. n8n runs the vision model, then calls `callback_url` (the validation-callback
   route above) with the verdict. The callback is idempotent.

`photo_url` and `callback_url` are built from `QUESTMAP_PUBLIC_URL`
(`http://host.docker.internal:8090` in dev) so n8n running on the host can reach
this backend.
