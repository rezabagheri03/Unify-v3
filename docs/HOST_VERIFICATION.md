# Host Verification Checklist (TODO-047 / TODO-036 host-side)

Run this once on the Pars Pack host after first deploy. It converts the audit
series' theoretical items into measured facts. All commands run from the
`unify-backend/` directory unless noted.

## PHP/runtime
- [ ] `php -v` → 8.2.x
- [ ] `php -m | grep -iE 'fileinfo|intl|gd|zip|sodium'` → all present
      (`fileinfo` is REQUIRED — the upload pipeline rejects files by finfo result)
- [ ] `php -i | grep opcache.enable` → `On` (if a cPanel toggle exists, enable OPcache;
      this is the single biggest TTFB win on shared hosting)
- [ ] `php -i | grep -E '^memory_limit'` → ≥ 128M **and** ≥ the 50 MB upload cap
      (`MAX_FILE_SIZE_MB=50`); `upload_max_filesize`/`post_max_size` ≥ 50M
- [ ] `php -i | grep max_execution_time` → ≥ 120 for web SAPI if settable;
      imports and envelope ZIP runs raise their own `set_time_limit`
- [ ] `php artisan about` → environment `production`, debug **off**, cache/config/route cached

## Database
- [ ] `php artisan migrate:status` → all 20 migrations `Ran` (incl. the two
      `2026_08_11_*` hardening migrations)
- [ ] notifications ENUM accepts the new types (insert probe:
      `registration_open`, `grace_ended`, `calendar_warning`)
- [ ] MySQL timezone = app expectation (`SELECT NOW();` inside Tehran offset)

## Filesystem / storage
- [ ] `storage/` writable by the PHP user (uploads land on the **local** disk since TODO-001)
- [ ] `public/.htaccess` headers live: `curl -sI https://<host>/api/v1/health | grep -iE 'content-security|referrer-policy|x-frame'`
- [ ] JSON compressed: `curl -sI -H 'Accept-Encoding: gzip' https://<host>/api/v1/branding | grep -i content-encoding`
- [ ] Frontend (public_html) immutable caching:
      `curl -sI https://<host>/assets/<hashed>.js | grep -i cache-control` → `max-age=31536000, immutable`
- [ ] Deep links survive: `curl -s -o /dev/null -w '%{http_code}' https://<host>/dashboard` → 200 (SPA fallback, not 404)

## Scheduler
- [ ] crontab entry exists: `*/5 * * * * cd <backend> && php artisan schedule:run >> /dev/null 2>&1`
- [ ] after 24h: `storage/logs/laravel.log` shows no repeated scheduler errors;
      `storage/app/backups/db/` and `.../files/` have fresh archives; oldest ≤ 14 days
- [ ] `php artisan schedule:list` matches `docs/CRON_TABLE.md`

## Security spot-checks
- [ ] login throttle: 6th wrong password → 429 (LOGIN_THROTTLE 5/15)
- [ ] token expiry: a token older than `SANCTUM_TOKEN_EXPIRATION_MINUTES` → 401
- [ ] pending resource file NOT fetchable before approval (SEC-04):
      request its download URL as another student → 403/404
- [ ] push OFF by default: `PUSHE_ENABLED=false` in production `.env` unless
      deliberately enabled (D-006)

## External uptime (TODO-047 core)
- [ ] Add an external monitor (UptimeRobot/Betterstack free tier) →
      `GET https://<host>/api/v1/health` every 5 min, alert on non-200 or
      body not containing `"status":"ok"`. This endpoint is intranet-safe by
      design (probe results are cached 60 s since TODO-021).
