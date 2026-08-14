# Unify V9 - FINAL - Ready for Pars Pack Cloud Host + Host Iran
## No VPS / No Self-Host / No iOS - 600 Students Computer Engineering - Shared Host Ready

**This is the new source of truth. Replaces V7 and V8.**
**Hosting Target:** https://parspack.com/host/cloud-host + https://parspack.com/host/iran
**Stack:** React PWA (static) + PHP Laravel 10 + MySQL + Local Filesystem + Cron + Polling
**Android:** Optional APK direct download hosted on same host (no Google Play needed)

### Why V9?
- User confirmed: VPS too expensive / weak / low traffic, university cannot self-host
- User wants to use only: Cloud Host + Host Iran (both shared hosting, inside Iran, SHOMA connected, unlimited bandwidth, daily backup)
- We keep ALL business features, degrade only real-time (WebSocket -> polling 15s) and scalability (2000 concurrent -> 200-300)

### What Changed From V8?
- FastAPI -> Laravel 10 PHP 8.2
- PostgreSQL -> MySQL 8 (unlimited databases on Cloud Host)
- Redis -> File cache + MySQL cache + Memcached (available on Cloud Host) + MySQL table for idempotency
- MinIO S3 -> Local `/public_html/uploads` with LRU cleanup via cron (10GB Shop plan)
- Celery -> cPanel Cron `* * * * * php artisan schedule:run`
- WebSocket internal `wss://unify.local` -> Polling `GET /api/notifications/unread?since=` every 15s
- Docker -> cPanel Git deploy / FTP upload
- SQLite + SQLCipher -> IndexedDB (idb-keyval) for web only (no native SQLite needed for PWA)
- Socket.IO separate server removed - single Laravel app handles API + cron
- iOS removed entirely
- Android APK still possible: Build Capacitor Android wrapper that points to `https://unify.yourdomain.ac.ir` + calls Pushe API via PHP backend

### Hosting Plans Selected for 600 Students
From https://parspack.com/host/cloud-host official page:

- **Starter:** 1GB SSD / 1 vCPU / 2GB RAM / Unlimited Bandwidth / Unlimited DB / Daily DB backup + Weekly full = 229,500 Toman/month
- **Startup:** 5GB SSD / 3 vCPU / 4GB RAM / Unlimited Bandwidth = 341,600 to 420,750 Toman/month
- **Shop:** 10GB SSD / 5 vCPU / 7GB RAM / Unlimited Bandwidth = 588,000 to 716,550 Toman/month

For 600 CS students, **RECOMMENDED: Shop 10GB/5vCPU/7GB RAM 716k** - gives you 7GB RAM, 5 cores, enough for enrollment peak 200 concurrent. Start with Startup 4GB RAM 341k for MVP, upgrade in panel with zero downtime.

### Docs Structure V9
- `V9_DEFINITIVE.md` - Full definitive spec for shared host (replaces V7)
- `DEPLOYMENT_GUIDE.md` - Step by step cPanel deploy to Pars Pack Cloud Host + Host Iran
- `STACK_MIGRATION_V8_TO_V9.md` - What we lose/degrade (honest table)
- `COMPATIBILITY_MATRIX.md` - Feature vs Host compatibility
- `ROLES/` - Updated roles (no change business logic, only technical notes for PHP)
- `FEATURES/` - Updated 20 features with PHP/Laravel implementation + cron + polling
- `PAGES/` - Same pages, no change (React frontend unchanged)

END
