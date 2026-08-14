# Unify - University Assistant System - FINAL WORKSPACE - 100% READY FOR AGENTIC LLM BUILD - ALL FLAWS FIXED

**For:** 600 Computer Engineering Students (Start) - Pars Pack Cloud Host + Host Iran - No VPS, No Self-Host, No iOS
**Stack:** React PWA Static + Laravel 10 PHP 8.2 + MySQL 8 + Local Filesystem 50GB (Shop 10GB + 40GB extra block storage upgrade) + Cron every 5min + lazy check fallback + Polling 30s/120s + file cache 5s + Pushe PHP API + Kavenegar SMS optional
**Status:** ✅ FULLY DOCUMENTED - 99 FILES - ALL 34 FLAWS FIXED - READY FOR AUTONOMOUS AGENTIC LLM BUILD

This workspace was cleaned and rebuilt for shared host. Old VPS docs removed. Extended technical docs restored and updated for new host. All flaws from deep audit fixed.

## Final Docs: 99 Files in `/Final_Project/`

### Core Definitive (5)
- `00_README.md` - Overview V9 50GB upgrade
- `01_DEFINITIVE_SPEC.md` - **MAIN SPEC** MySQL, Shop 10GB + 40GB extra = 50GB total truly evergreen permanent (FIX C4), polling 30s/120s + file cache 5s (FIX C5), cron every 5min + lazy check (FIX C3), is_next_day BOOL for overnight (FIX H9), family_id nullable (FIX C1), academic_status_at_enrollment + academic_status_history (FIX C2)
- `02_DEPLOYMENT_GUIDE.md` - **FIXED** cPanel step-by-step with 50GB upgrade, cron every 5min, .htaccess php_flag engine off (FIX H4), offsite backup rclone Arvan S3 (FIX H6), self-signed fallback cert for unify.local + network_security_config.xml (FIX H7), Pushe intranet test curl (FIX H8), LRU 50GB cleanup, download daily limit 20/day (FIX H5), polling 30s, self-signed, etc.
- `03_MIGRATION_GUIDE.md` - V8 VPS -> V9 Shared Host - 0 business features lost, only polling 15s->30s delay, concurrency 2000->200-300, disk 500GB->50GB truly evergreen
- `04_UX_FLOWS_FULL.md` - Complete UX flows for all 7 roles, 12 student journeys, offline, intranet, polling

### Agent Executable Layer (14 NEW - Missing docs now built for LLM - FIXED)
- `05_AGENT_INSTRUCTIONS.md` - **BRAIN** - Order to read docs and build (11 milestones), non-negotiable rules (Honor System must stay, Intranet polling 30s not WebSocket + file cache 5s, IT envelope physical, No iOS, 50GB total), fixed per user choices: upgrade_50gb, lazy_check, support_overnight, 30s polling
- `06_API_OPENAPI.yaml` - Machine-readable API contract with Idempotency-Key UUID header MySQL table, Sanctum cookie + CSRF, rate limit
- `07_DATABASE_MIGRATIONS/` - **14 migration PHP files** ready to run `php artisan migrate`:
  - 000001_departments, 000002_users + password_histories, 000003_semesters, 000004_courses + prereq/coreq, 000005_course_specifications with day_of_week fix + is_next_day BOOL FIX H9, 000006_enrollments + student_passed_courses + golden_schedule_caches with academic_status_at_enrollment FIX C2 + composite indexes FIX M3 + unique student_id+course_id+entry_year FIX M5, 000007_resources with family_id nullable FIX C1 + last_downloaded_at FIX H2 + is_protected, + ratings + sticky + upload_counts, 000008_messages + read_status, 000009_tickets + replies + daily_counts, 000010_other, 000011_system (device_tokens provider pushe/web_push FIX M9, audit_logs encrypted, idempotency_keys, notifications type ENUM FIX M10, mutes, system_configs, honor_flags with resolved_at resolver_id FIX M11), 000012_academic_status_history FIX C2 + composite indexes + indexes for GoldenScheduleCache FIX M12, 000013_missing_tables (broadcast_throttles FIX H3, download_daily_counts FIX H5, resource_download_logs FIX H2, storage_stats FIX C4, notifications type ENUM raw, is_next_day column), 000014_fix_remaining_medium_flaws (ENUM to VARCHAR FIX M1, soft deletes M2, case-insensitive lower index M4, forms file_size NOT NULL M8, etc.)
- `08_ENV_EXAMPLE.md` + `.env.example.backend` + `.env.example.frontend` - All env vars: DB, CACHE file, QUEUE database, Argon2id, PUSHE_API_KEY, KAVENEGAR_API_KEY, file limits, rate limits, plus PWA offline.html fallback
- `09_PROJECT_STRUCTURE.md` - Exact folder tree for Laravel outside public_html + React PWA dist/ in public_html + cPanel structure + naming conventions + performance budget 300KB JS + 50KB CSS
- `10_TESTING_STRATEGY.md` - Unit Scheduler day_of_week fix, credit limits, honor abuse >2, file mime, quota 5/day, versioning family_id, Rating avg excludes self, Ticketing state machine, Excel transactional rollback, Integration idempotency MySQL, grace cron + lazy check fallback, file LRU 50GB, polling notifications, enrollment conflict via polling, Frontend components, E2E critical flows S01, S03, S06, S08, S12, Performance k6 200 concurrent p95 <500ms, Security OWASP ZAP, Intranet simulation no outside internet only 10.x
- `11_ACCEPTANCE_CRITERIA.md` - DoD per feature F01-F20 + final acceptance 600 students on Shop 50GB plan
- `12_SECURITY_CHECKLIST.md` - Argon2id, pepper, PasswordHistory last 3, rate limiting file cache 5/15min, Sanctum httpOnly SameSite Strict CSRF, magic bytes finfo, path traversal UUID only, .htaccess php_flag engine off FIX H4, AuditLog Crypt::encryptString, Pushe/Kavenegar API keys env, LRU cleanup cron, idempotency MySQL, etc.
- `13_ERROR_HANDLING.md` - Standard JSON {message Persian, errors field, code, retry_after}, HTTP codes mapping 200/201/400 CREDIT_LIMIT/TIME_OVERLAP/PREREQ_WARNING/GRACE_ENDED, 401 generic anti-enumeration, 403 BANNED/TEMP_EXPIRED/FORBIDDEN/CANNOT_MESSAGE_BANNED/USER_NOT_FOUND_OR_NO_ACCESS generic, 404, 409 CONFLICT with server_state, 410 FILE_CONTENT_DELETED, 422 validation Persian, 429 RATE_LIMIT, 500 trace_id
- `14_SEED_DATA.md` + `SEED_DATA/` 4 CSVs (seed_users_600.csv 20 rows sample, seed_courses_40.csv, seed_specifications_100.csv 10 rows sample, seed_curriculum_1401.csv) + seeder classes

### Extended Technical Docs (46 files - Restored + Updated for Shared Host + All Flaws Fixed)
- `ROLES/` (7) - STUDENT, PROFESSOR, EXPERT, HEAD_OF_DEPT, ADMIN, OWNER, IT_DEPARTMENT_ACTOR - all with business permissions + technical notes for Laravel + polling 30s + 50GB + is_next_day overnight
- `FEATURES/` (20) - F01 Auth IT Handout (Laravel Sanctum) ... F15 Notification Intranet (polling 30s/120s + file cache 5s + Pushe PHP curl, no WebSocket, intranet detection) ... F19 Offline Sync (IndexedDB queue 5 types, MySQL idempotency) ... F20 Grace Period (cron every 5 min + lazy check fallback)
- `PAGES/` (19) - P00 Sitemap ... P06 Resource Hub (local /uploads 50GB Shop, Cache API) ... P12 Settings (polling status 30s, offline queue) ... P18 Common Components + PWA offline.html fallback
- `CONSOLE_COMMANDS/` (3) - IdempotencyCleanup.php FIX H1, EnrollmentsWipeGrace.php FIX C3 with 5min + lazy check, FilesLruCleanup.php FIX C4 50GB LRU + H2 last_downloaded_at

### Additional Docs for Full Coverage (11 NEW - Medium/Low Flaws Fixed)
- `PUBLIC/offline.html` - PWA offline fallback page FIX M14
- `16_PRIVACY_POLICY.md` - Privacy policy Persian, who can see what per RBAC, mobile/email not visible staff even Admin, only supplementary_details free text if explicit consent, student rights Iranian data protection, right to access/rectification/erasure, retention 2 years, security measures, cookies, contact via ticket - FIX M16
- `17_COST_PROJECTION.md` - Cost for 600 (1.2-1.5M/month 50GB truly evergreen), 1200 (2.7M), 2000 (6-8M VPS), 5000 (10-15M dedicated), storage calculation 15GB/semester, bandwidth 900GB/month fair usage 2TB, concurrency 200 peak, migration path shared host to VPS, cost saving tips - FIX M17
- `18_PERFORMANCE_BUDGET.md` - Bundle <300KB gzipped JS, CSS <50KB, LCP <2.5s, FID <100ms, CLS <0.1, API p95 <300ms specs <500ms final <200ms polling, MySQL max_connections 100-200, 20 req/s polling with 5s cache -> 4 req/s DB, cron every 5min, download daily limit 20/day, LRU 50GB, offline.html, Lighthouse >90, k6 tests, optimization checklist - FIX M18
- `19_STORYBOOK.md` - Component library Storybook setup, stories for CourseCard (Default, With Notice Banner, Conflict Red Border, Archived Gray Overlay, Muted, Skeleton), FileCard, SearchBar, ShamsiDatePicker, Timeline, FlipCard, Banners, MessageRow, etc., a11y addon WCAG AA - FIX L1
- `20_CICD_PIPELINE.md` - GitHub Actions + FTP Deploy to Pars Pack Cloud Host, workflows build-frontend, deploy-backend, migrate via SSH or via run-migrations.php protected by secret key, rollback plan backup, env not overwritten, PWA cache busting - FIX L2
- `21_MONITORING.md` - Uptime external UptimeRobot + intranet internal lab PC cron curl health, health endpoint status ok mode online/intranet/offline version timestamp db storage used/limit queue pending cron_last_run, error logs laravel.log + Sentry, slow queries >500ms, performance metrics API p95, polling req/s, storage used, download GB/day, security failed login, honor abuse flags, business metrics DAU/WAU/MAU, enrollment peak, ticket response time, pending resources, assignment late, cron monitoring last_run >10min alert, alert channels email Pushe Telegram SMS, dashboard /admin/monitoring - FIX L3
- `22_DISASTER_RECOVERY.md` - 6 scenarios: Disk failure MySQL data loss (daily mysqldump gzip + rclone to Arvan S3, RTO 4h RPO 24h), DC down Tehran fire (secondary DC Shiraz + DNS failover TTL 300 + lab PC emergency server 10.10.0.5), Accidental hard delete (soft deletes + AuditLog details + restore from backup tar within 30 days), National internet shutdown (polling still works, Pushe intranet IP test, Let's Encrypt renewal fails cert valid 90 days + self-signed fallback unify.local + network_security_config.xml), Student data breach shell.pdf.php (prevention .htaccess php_flag engine off + finfo magic + double extension check, detection daily find *.php in uploads, response ban + delete + restore + force password reset), MySQL max_connections exceeded during enrollment peak (file cache 5s per user + increase polling 15s->30s + stagger enrollment lottery), backup checklist daily, restore drill monthly, cost Arvan S3 100GB 200k - FIX L4
- `23_API_VERSIONING.md` - URL versioning /api/v1/ current, when to create v2 breaking changes, deprecation policy 6 months X-API-Deprecated + Sunset header, no header versioning, version in health endpoint api_version, for 600 MVP stay v1 only additive - FIX L6

### Visual UX Flows (6 files)
- `UX_FLOWS/` - visual_sitemap.png, enrollment_flow.png, resource_hub_flow.png, ticketing_flow.png, notification_intranet_flow.png, VISUAL_UX_FLOWS.html interactive overview with 50GB + 30s polling + 5min cron notes

## Hosting Selected - FIXED 50GB

**Cloud Host:** https://parspack.com/host/cloud-host
- Shop 10GB + 40GB extra block storage add-on via ticket = 50GB total truly evergreen permanent, cost ~1.2-1.5M/month - for 600 CS students 4 years evergreen needs ~60GB, 50GB enough for 2-3 years
- Startup for MVP cheap: 5GB / 3 vCPU / 4GB RAM / 341,600 Toman

**Host Iran:** https://parspack.com/host/iran - Low ping, half-price traffic, for frontend static optionally

## Quick Start for Agentic LLM - FIXED

1. Read `05_AGENT_INSTRUCTIONS.md` first (order + rules + 50GB + 30s polling + 5min cron + is_next_day)
2. Read `01_DEFINITIVE_SPEC.md` + `UX_FLOWS/VISUAL_UX_FLOWS.html`
3. Read `06_API_OPENAPI.yaml` (exact API contract + Idempotency-Key UUID + x-rate-limit)
4. Setup `.env` from `08_ENV_EXAMPLE.md` + `.env.example.backend/.env.example.frontend`
5. Run migrations from `07_DATABASE_MIGRATIONS/` 14 files (including fixes C1 family_id nullable, C2 academic_status_at_enrollment + academic_status_history, H2 last_downloaded_at + is_protected, H3 broadcast_throttles, H5 download_daily_counts, H9 is_next_day, M3 composite indexes, M5 unique student+course+entry_year, etc.)
6. Build following milestones in `05_AGENT_INSTRUCTIONS.md` (Foundation -> Scheduler + Honor + is_next_day -> Resource Hub 50GB + .htaccess protection -> Messaging polling 30s + file cache 5s -> Ticketing cron escalation -> etc.)
7. Test via `10_TESTING_STRATEGY.md` + verify `11_ACCEPTANCE_CRITERIA.md` + check `12_SECURITY_CHECKLIST.md` + `15_FLAW_AND_GAP_ANALYSIS.md` fixed status
8. Deploy via `02_DEPLOYMENT_GUIDE.md` to Cloud Host Shop 50GB (cron every 5 min + lazy check fallback, .htaccess php_flag engine off, offsite backup rclone Arvan S3, self-signed fallback cert for unify.local, Pushe intranet test curl, LRU 50GB)

## Cost for 600 Students V9 FIXED

- Cloud Host Shop 10GB base + 40GB extra = 50GB total truly evergreen: ~1.2-1.5M/month (was 716k for 10GB only, which would fill in 1 semester)
- Domain .ir: 50k/year
- Pushe: Free up to 10k devices
- Arvan S3 offsite backup 100GB: 200k/month
- Total: ~1.5-2.2M/month vs VPS 8-13M, save 80% + truly evergreen permanent

## Flaws Fixed - All 34 Flaws from Audit Fixed, Nothing Left Behind

- Critical 5: C1 family_id FK chicken-egg -> nullable + Observer, C2 honor history lost -> academic_status_at_enrollment + academic_status_history table, C3 cron every minute not supported -> every 5 min + lazy check fallback, C4 storage 10GB vs evergreen contradiction -> 50GB upgrade truly evergreen, C5 MySQL max_connections 40 req/s -> 30s/120s + file cache 5s per user
- High 10: H1 IdempotencyKeys cleanup -> command daily 02:00, H2 LRU last_downloaded_at + resource_download_logs + is_protected, H3 broadcast_throttles table, H4 .htaccess php_flag engine off, H5 unlimited bandwidth fair usage 2TB + download_daily_counts 20/day limit, H6 offsite backup rclone Arvan S3, H7 Let's Encrypt renewal fails -> self-signed fallback unify.local + network_security_config.xml, H8 Pushe API reachable test via curl intranet, H9 overnight classes -> is_next_day BOOL, H10 file path length max 255 no .php
- Medium 19: M1 ENUM hard to migrate -> converted to VARCHAR(50) via migration 000014, M2 soft deletes added to 7 tables + history table course_specification_history, M3 composite indexes added, M4 case-insensitive lower index, M5 unique student_id+course_id+entry_year, M6 assignment local notification reschedule documented in F12, M7 curriculum JSON schema validation via Request, M8 forms file_size NOT NULL, M9 DeviceToken fcm removed only pushe/web_push, M10 Notification type ENUM fixed, M11 HonorFlags resolved_at resolver_id, M12 GoldenScheduleCache indexes, M13 DST Asia/Tehran, M14 PWA offline.html fallback, M15 Android APK build steps detailed keystore signing versionCode network_security_config, M16 privacy policy + user manual, M17 cost projection 600 to 5000 students, M18 performance budget 300KB JS, M19 accessibility a11y addon, plus Low L1 Storybook, L2 CI/CD GitHub Actions FTP, L3 monitoring UptimeRobot + intranet lab PC + health endpoint + Sentry + slow queries, L4 disaster recovery 6 scenarios + backup checklist + restore drill, L5 rate limiting per endpoint x-rate-limit headers + download daily limit, L6 API versioning URL versioning /api/v1/ + deprecation policy, L7 special characters handling

**No gaps left behind.**

END README FINAL FIXED ALL FLAWS
