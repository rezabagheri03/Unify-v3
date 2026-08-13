# Cron / Scheduler Verification Table (TODO-045)

**Authoritative source:** `unify-backend/routes/console.php` (Laravel 11; there
is no `app/Console/Kernel.php`). Single host cron entry (`*/5 * * * *
php artisan schedule:run`) fans out to everything below. Timezone:
`Asia/Tehran` (`APP_TIMEZONE`).

Verified 2026-08-11 against the command signatures in
`unify-backend/app/Console/Commands/`: every scheduled entry maps to a real
signature; no command is scheduled twice; the two restore commands are
deliberately **manual-only** (disaster recovery).

| Time | Entry | Signature / target | Purpose | Notes |
|---|---|---|---|---|
| every 5 min | `enrollments:wipe-grace` | `EnrollmentsWipeGrace` | F20/C3: wipe temp enrollments after the grace window | No-op until `activateCurrent` sets `grace_period_ends_at` (TODO-008) |
| 01:00 | `storage:calculate-stats` | `StorageCalculateStats` | C4: recompute `system_configs.storage_used_bytes` | Read by `GET /monitoring/storage` + owner analytics |
| 02:00 | `idempotency:cleanup` | `IdempotencyCleanup` | H1: purge expired idempotency keys | |
| 02:15 | closure `golden-cache-purge` | `GoldenScheduleCache` expired-row delete | PERF-09: bound the shared golden-schedule cache | Scheduled closure, not a signature |
| 02:30 | `backup:database --compress` | `BackupDatabase` | Nightly DB dump + `--keep-days=14` prune | Target: `storage/app/backups/db` |
| 03:00 | `resources:cleanup-old-versions` | `ResourcesCleanupOldVersions` | F05: purge superseded file content after 30 days | Sets `is_deleted_content` (row kept — D-012) |
| 03:30 | `backup:files --compress` | `BackupFiles` | Nightly storage archive + prune | Real dirs only (D-audit rewrite) |
| 04:00 | `files:lru-cleanup` | `FilesLruCleanup` | F05/C4: LRU eviction under 80% of the 50 GB cap | Content tombstones, never rows |
| 04:30 | `sanctum:prune-expired --hours=24` | framework | SEC-03: drop expired personal-access tokens | Token expiry = `SANCTUM_TOKEN_EXPIRATION_MINUTES` (default 7d) |
| 08:00 | `calendar:warn` | `CalendarWarn` | F11: 7-day + 24-hour academic-calendar warnings | Post-TODO-018: current-semester, dept-scoped, deduped |
| hourly | `tickets:escalate` | `TicketsEscalate` | F08: L1 (48h silent) → staff/admin; L2 (+48h) → owner/admin | Thresholds: `config/unify.php` |
| hourly | `assignments:mark-late` | `AssignmentsMarkLate` | F12: flip expired trackers to `late` | |

**Manual-only (never schedule):** `restore:database {file}`, `restore:files {file}` — see [DISASTER_RECOVERY.md](../DISASTER_RECOVERY.md).

**Backup verification (ops):** after the first night on the host, confirm
`storage/app/backups/db/` and `storage/app/backups/files/` contain fresh
archives, and that pruned sets never exceed 14 days.
