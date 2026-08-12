# F19 Offline Sync & Conflict Resolution - V9 Shared Host (IndexedDB + Polling + MySQL Idempotency)

## Purpose
Offline-first pattern with optimistic updates, sync queue, smart merge, idempotency via MySQL (no Redis), Workbox PWA - Simplified for shared host, only 5 safe entity types queued

## Local Storage V9 (No SQLite)
- IndexedDB via idb-keyval (2KB wrapper) NOT capacitor-sqlite + SQLCipher
- Stores: CachedUser (user_id, user_json, last_sync), CachedSpecifications, CachedEnrollments, CachedResources metadata + file cache paths via Cache API, CachedMessages, CachedTickets, CachedCurriculum, CachedForms, CachedCalendar, UserPreferences, NotificationMuteLocal
- SyncQueue IndexedDB key `unify-syncQueue` array of {id UUID, entity_type ENUM enrollment_temp? No, only 5 safe: rating, sticky_note, ticket_create, ticket_reply, assignment, curriculum_passed, message_send, entity_type, action create/update/delete, payload JSON, idempotency_key UUID, status pending/syncing/synced/failed/conflict, attempts INT, last_error TEXT, created_at}
- FileCache via Cache API not SQLite table, File metadata IndexedDB FileCacheMeta {file_id, family_id, path, size, last_accessed, is_pinned, is_protected}, LRU eviction via Workbox LRU 100MB
- No SQLite tables, no SQLCipher key derivation

## Sync Strategy V9 Simplified (No Redis, MySQL Idempotency)

### Background Sync
- Every 2 min when app foreground and online (navigator.onLine true), and via Workbox Background Sync plugin, When background pause to save battery resume foreground
- Idempotency: Every mutating request client generates Idempotency-Key UUID v4, stored in SyncQueue row + sent as header Idempotency-Key, Server MySQL table IdempotencyKeys {key VARCHAR(36) UNIQUE, user_id FK, response_code INT, response_body JSON, created_at, expires_at 24h} - no Redis SETEX, MySQL INSERT. If server sees duplicate key with same user within 24h returns previous response without reprocessing
- Processing Order FIFO per user global to maintain causality, one by one not parallel, attempts exponential backoff 2^attempts seconds max 5 attempts then status failed shows in Offline Queue page retry button

### Smart Merge Per Entity Type V9 Simplified

#### Enrollment Temp - NOT QUEUED in V9
Requires online, no offline queue, no conflict resolver needed for MVP, If offline shows "برای افزودن نیاز به اینترنت است" and disable Add button

#### Rating, Sticky, Assignment, Curriculum Passed, Ticket Create/Reply, Message Send - QUEUED
- Rating: Last write wins via rated_at timestamp compare client vs server latest wins
- Sticky Note: Last write wins via updated_at
- Curriculum Passed Checkbox: OR merge - if local true OR server true keep true (once passed true stays true unless explicit uncheck confirmation), For uncheck require modal "آیا مطمئنید این درس را پاس نکرده‌اید؟" then set false and sync
- Assignment Tracker: Last write wins for title/desc/due date but status transitions rules pending->submitted allowed submitted->graded only professor cannot graded->pending via student
- Ticket Create: No conflict, Reply: No conflict append, Status change optimistic locking version server wins if conflict
- Messaging Send: No conflict create new row

### Offline Read vs Write V9
- Read: Trust local cache via Workbox runtime cache for display, show offline banner if data stale last_sync >1h
- Write queueable 5 types: rating, sticky, ticket create/reply, assignment create/submit, curriculum checkbox, message send/reply, profile edit supplementary - allow offline queue
- Write requires online: enrollment final, resource upload, broadcast, ban, set semester, approve resource, etc. - show message "برای این کار نیاز به اینترنت است" and disable button

### Queue UI Page Offline Queue Settings
Settings -> "وضعیت همگام‌سازی آفلاین" page List pending/syncing/synced/failed/conflict rows with entity_type icon action summary created Shamsi status badge attempts last_error Actions Retry failed Delete pending Cancel Resolve conflict opens conflict resolver modal, Top stats Pending count Last sync time Button "همگام‌سازی اکنون" forces sync if online, Button "پاک کردن کش فایل‌ها" clears Cache API except pinned/protected, Button "حذف و بازسازی دیتابیس لوکال" deletes IndexedDB and re-fetches from server on next online

### Polling vs SyncQueue Coexistence V9
- Polling for server->client (notifications, spec updates) every 15s
- SyncQueue for client->server writes when offline or online (client push) every 2 min + Workbox Background Sync
- When online writes go directly via API immediately and SyncQueue row marked synced immediately without queue processing, When offline writes go to queue only, When online but API fails fallback to queue

### Conflict Resolver UI V9 Simplified
When status conflict (rare for queued 5 types, mostly for enrollment not queued now) show modal "تداخل سینک برای {entity}: نسخه سرور و نسخه شما متفاوت است" Shows server vs local diff Options Keep Server discard local Keep Mine overwrite server if allowed Merge (for curriculum OR)

## API Endpoints for Sync V9
GET /api/v1/sync/status returns server time last sync pending jobs count
POST /api/v1/sync/push? Actually direct endpoints with idempotency not generic sync push

## Edge
Queue 100 pending user goes online sync one by one progress, fails 5 times status failed show error retry, Idempotency key collision unlikely UUID v4, IndexedDB corrupted delete and recreate from server on next online login warning, User clears app cache FileCache cleared SyncQueue cleared need re-login

## Testing V9
Integration tests Simulate offline queue 10 actions go online verify all synced order, verify idempotency same key twice server returns same response second time without double create, test OR merge for curriculum checkbox
