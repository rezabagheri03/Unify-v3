# Stack Migration V8 (VPS) -> V9 (Pars Pack Cloud Host + Host Iran) - What Changes

## Summary
V8 was: FastAPI + Postgres + Redis + MinIO + WebSocket + Celery + Docker + SQLite + Capacitor Android + iOS
V9 is: Laravel + MySQL + Local Files + Polling + Cron + cPanel + React PWA static + Android APK optional

## Detailed Changes

| Layer | V8 | V9 Shared Host | Migration Effort | Feature Loss? |
| :--- | :--- | :--- | :--- | :--- |
| **Frontend** | React 18 + Vite PWA + Zustand + idb-keyval + Workbox + MUI | Same React PWA, same Zustand + idb-keyval, same PWA | 0 days - No change, just build and upload dist/ to public_html | No loss |
| **Backend Language** | Python FastAPI | PHP 8.2 Laravel 10 | 60 days - Rewrite all API endpoints, models, policies | No business loss, only language |
| **Database** | PostgreSQL 15 | MySQL 8 (unlimited DB on Cloud Host) | 5 days - Convert migrations PostgreSQL -> MySQL, JSON type still works MySQL 8, ENUM same | No loss, MySQL 8 supports JSON, but loses Postgres LISTEN/NOTIFY (not used) and tsvector full-text (replace with MySQL FULLTEXT) |
| **ORM** | SQLAlchemy 2.0 async | Eloquent ORM sync | Part of rewrite | No loss |
| **Cache** | Redis 7 + File | File cache + MySQL cache driver + Memcached (available on Cloud Host) | 2 days - Replace Redis::get with Cache::get (file driver) | Degraded: No Redis pubsub, use MySQL table for pubsub via polling |
| **File Storage** | MinIO S3 self-hosted, signed URLs 15min, bucket | Local `/public_html/uploads/resources/...` + LRU cleanup via cron daily, direct URLs | 3 days - Replace S3 client with Storage::disk('public') local | Degraded: 10GB limit vs 2TB infinite, no signed URLs, direct public URLs |
| **Real-time** | FastAPI WebSocket `/ws` + Redis PubSub + Socket.IO removed already in V8 | Polling GET /api/notifications/unread?since= every 15s via setInterval + Workbox Background Sync | 2 days - Remove WebSocket client, add polling hook useNotificationsPolling() | Degraded: 0s -> 15s delay, but intranet still works because polling is HTTP |
| **Push Android** | Pushe via FastAPI calling Pushe API | Pushe via Laravel HTTP client Guzzle calling same Pushe API | 1 day - Same API, just PHP curl | No loss - Pushe works from PHP too |
| **iOS** | Dropped already in V8 | Dropped (user requested) | 0 | No loss, already dropped |
| **Background Jobs** | Celery + Redis beat: grace wipe every minute, ticket escalation hourly, calendar warnings daily | Laravel Scheduler + cPanel Cron `* * * * * php artisan schedule:run` | 3 days - Rewrite Celery tasks as Artisan Commands | Degraded: Cron minute precision vs Celery second precision, if cron skips under load, jobs delayed |
| **Offline Sync** | IndexedDB queue for 5 types (rating, sticky, ticket, assignment, curriculum) + MySQL idempotency table | Same IndexedDB queue (frontend unchanged), backend idempotency via MySQL table IdempotencyKeys instead of Redis | 1 day - No change frontend, backend replace Redis SETEX with MySQL INSERT | No loss |
| **Auth** | Argon2id + JWT access 15min + refresh 7d rotation reuse detection | Laravel Sanctum SPA cookie + Argon2id via `Hash::make` with `argon2id` driver, refresh via Sanctum token expiration | 2 days - Rewrite login to Sanctum | No loss, Argon2id still available PHP has PASSWORD_ARGON2ID |
| **Rate Limiting** | Redis throttle 5/min per IP | Laravel throttle middleware using file cache or MySQL cache (less efficient) | 1 day | Degraded: Slightly slower, but still works |
| **Excel** | openpyxl server + SheetJS client | PhpSpreadsheet server + SheetJS client | 2 days - Rewrite import/export service | No loss |
| **Shamsi** | date-fns-jalali JS + jalaali-js server | date-fns-jalali JS + Morilog\Jalali PHP | 1 day | No loss |
| **AuditLog Encryption** | pgcrypto pgp_sym_encrypt | Laravel Crypt::encryptString with APP_KEY | 1 day | No loss |
| **Deployment** | Docker Compose: app + postgres + redis + minio + celery worker/beat + nginx, single VPS | cPanel: Upload backend to `unify-backend` outside public_html, React build to public_html, create MySQL DB via cPanel, set cron, SSL Let's Encrypt via cPanel | 3 days - Write deployment guide + .htaccess | No Docker needed, simpler for shared host |
| **Hosting** | Iranian VPS 4c/16GB/500GB 6-10M Toman + backup VPS 2M = 8-13M | Cloud Host Shop 10GB/5vCPU/7GB RAM 716k Toman + Host Iran for frontend optional 200k = 916k total or single 716k | Cost save: 8M -> 0.7M, save ~90% | Degraded: Concurrency 2000 -> 200-300, disk 500GB -> 10GB, but for 600 students okay |

## Effort Estimate

- Rewrite backend FastAPI -> Laravel: 40-50 days for one senior PHP dev (if you have existing V7 spec, just port endpoints)
- Frontend: 0 days (same React)
- Testing: 10 days
- Deployment + Cron + Pushe integration: 3 days
- Total: ~60 days (2 months) for one dev.

If you use Pars Pack PaaS Python instead of Cloud Host, effort 0 days (keep FastAPI), just deploy to PaaS.

## What You Lose - Honest Final Table

| Feature | V8 VPS | V9 Shared Host | Lost? |
| :--- | :--- | :--- | :--- |
| Business features (scheduler, resources, tickets, etc.) | Yes | Yes | **No loss** |
| Intranet must-have | WebSocket internal instant | Polling 15s HTTP, still works on intranet | Degraded 15s delay, not lost |
| Android push background | Yes via Pushe | Yes via Pushe PHP curl | No loss |
| File hub evergreen | MinIO 2TB infinite | Local 10GB limit, LRU cron | Degraded limit |
| Real-time exam flip? Frontend only | Yes | Yes | No loss |
| Concurrency enrollment peak | 2000 | 200-300 before CPU throttle | Degraded |
| Offline queue 5 types | Yes | Yes | No loss |
| Honor system + IT handout | Yes | Yes | No loss |
| Cost per month for 600 students | 8-13M Toman | 0.7M Toman | Save 90% |

## Recommendation

For 600 students, V9 shared host is **acceptable for MVP** if you can live with polling 15s instead of instant push and 10GB disk limit.

If you want to keep Python stack and pay similar price (550k-950k), use **Pars Pack PaaS Python** (https://parspack.com/paas/python) - it's same company, same panel, hourly 35 Toman, supports FastAPI + Postgres, isolated containers, not shared host, no rewrite needed. That is actually the best middle ground between VPS expensive and shared host too limited.

END MIGRATION GUIDE
