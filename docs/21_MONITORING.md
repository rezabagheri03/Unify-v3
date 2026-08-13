# 21 - Monitoring & Alerting - V9 Shared Host

## Purpose
Shared host has no built-in monitoring like Datadog, need DIY monitoring for 600 students, especially during enrollment peak and intranet shutdown.

## What to Monitor

### 1. Uptime

- **External Uptime (when internet online):** Use free service UptimeRobot or BetterStack to ping `https://unify-cs.ac.ir/api/v1/health` every 1 min, alert via email + Telegram if down
- **Intranet Uptime (when international gateway cut):** External uptime check will fail during national shutdown even if intranet works, so need internal monitoring: On university lab PC with internal IP 10.10.0.x, run cron every minute `curl https://unify-cs.ac.ir/api/v1/health` (via internal DNS override to 10.10.0.5) and log to file, if fails 3 times, send SMS via Kavenegar API to Admin

### 2. Application Health Endpoint

**Already defined in OpenAPI:** `GET /api/v1/health`

Implementation in Laravel:

```php
Route::get('/health', function() {
  return response()->json([
    'status' => 'ok',
    'mode' => \App\Services\HealthService::detectMode(), // online/intranet/offline based on 8.8.8.8 reachable vs internal health reachable
    'version' => config('app.version', '9.0.0'),
    'timestamp' => now()->toIso8601String(),
    'db' => DB::connection()->getPdo() ? 'ok' : 'fail',
    'storage_used_bytes' => \App\Services\FileCacheService::getTotalBytesUsed(), // for 50GB limit
    'storage_limit_bytes' => 50*1024*1024*1024,
    'queue_pending' => DB::table('jobs')->count(), // if using database queue
    'cron_last_run' => Cache::get('cron_last_run_at'),
  ]);
});
```

### 3. Error Logs

- **Laravel logs:** `storage/logs/laravel.log` on shared host, cPanel File Manager can view, but better to send to external service like Sentry (free tier) via `sentry/sentry-laravel` package, DSN from env `SENTRY_DSN`
- **PHP errors:** cPanel -> Errors -> Error Log shows PHP fatal errors, check daily
- **MySQL slow queries:** cPanel -> MySQL -> Slow Query Log? May not be available on shared host, alternative: In Laravel, enable query log for slow queries >500ms via Middleware `DB::listen` and log to file if time >500ms

### 4. Performance Metrics

- **API response time p95:** Log via Middleware `LogResponseTimeMiddleware` that measures request time and logs if >500ms for POST /api/v1/enrollment/final, >300ms for GET /api/v1/specifications
- **Polling load:** Monitor `GET /api/notifications/unread` count per minute via MySQL `notifications` table index (user_id, read, created_at) - if slow query log shows this query slow, add file cache 5s per user as fixed C5
- **Storage usage:** Cron daily `storage:calculate-stats` calculates total bytes used in `/uploads/resources` + `/uploads/forms` + `/uploads/assignments`, stores in `storage_stats` table, if >40GB (80% of 50GB) triggers LRU cleanup and sends notification to Admin via polling + Pushe "فضای ذخیره‌سازی ۸۰٪ پر شد - پاکسازی خودکار انجام شد"
- **Download daily counts:** Monitor `download_daily_counts` table total_bytes per day, if >100GB per day (fair usage 2TB/month = ~66GB/day), send alert to Admin

### 5. Security Monitoring

- **Failed login attempts:** AuditLog `failed_login` count per IP per 15min >5 -> rate limiting 429, but also log to file and alert Admin if same IP fails 20 times in 1 hour (possible brute force)
- **Honor abuse flags:** `honor_flags` table count where resolved=false, if >10 unresolved flags, notify Expert dept via Notification
- **File upload abuse:** `resource_upload_counts` 5/day quota, if user hits quota 3 days in a row, flag suspicious
- **Banned users:** `users` where is_banned true count, if >10% of total users banned, alert Owner

### 6. Business Metrics (Analytics Limited)

- **Active users:** DAU/WAU/MAU from `last_login_at` where last_login_at > now-1 day/week/month
- **Enrollment peak:** During enrollment phase enrolling, monitor enrollments count per hour, if >200 concurrent finalizing at same second (from Laravel logs), alert Admin to stagger enrollment times
- **Ticket response time:** Average time between ticket created and first staff reply, if >24h, alert Expert
- **Resource approval queue:** Count pending resources where status pending, if >20 pending >24h, notify Expert/Admin
- **Assignment late:** Count assignments status late, if >50 late, notify Professor

### 7. Cron Monitoring

- **Cron last run:** In `app/Console/Kernel.php` schedule, after each command, set `Cache::put('cron_last_run_at', now())` and `Cache::put('cron_last_run_' . $command, now())`
- **Health endpoint returns `cron_last_run`**, if `cron_last_run` > 10 minutes ago (should be every 5 min), alert Admin "کران اجرا نشده - مهلت ۲۴ ساعته ممکن است پاک نشود"
- On shared host, cron may be disabled on Starter plan, so health check is critical

### 8. Alert Channels

- **Email:** Laravel Mail via SMTP (Pars Pack email or Gmail) for critical alerts: storage >80%, cron not run >10min, MySQL max_connections, banned >10%, honor abuse >10
- **Pushe Push:** For Admin/Owner via Pushe API critical priority
- **Telegram:** Optional: Create Telegram bot, send alert to Admin Telegram group via `https://api.telegram.org/bot{token}/sendMessage`
- **SMS Kavenegar:** For critical only (spec time change, grace ending <2h) if Admin opted-in SMS, but also for monitoring alerts: storage full, cron failed, etc.

### 9. Dashboard for Admin

- Create page `/admin/monitoring` (Admin only) shows:
  - Health endpoint data: status ok, mode online/intranet/offline, DB ok, storage used/limit with progress bar red if >80%, queue pending, cron last run
  - Charts: API response time p95 last 24h, Polling req/s last 24h, Storage used last 30 days, Download GB per day last 30 days, Failed login attempts last 24h
  - Tables: Recent errors from laravel.log last 10, Recent audit logs is_suspicious true last 10, Pending resources count, Open tickets count, Escalated tickets count

### 10. Cost

- UptimeRobot free 50 monitors 5min interval, enough for 1 domain
- Sentry free 5k events/month, enough for 600 students
- BetterStack free 10 monitors 3min, enough
- No extra cost, just setup

END MONITORING
