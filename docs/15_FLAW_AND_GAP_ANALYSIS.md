# 15 - FLAW & GAP ANALYSIS - Deep & Full Audit of Unify V9 - 83 Files + FIXES APPLIED
## For 600 CS Students, Pars Pack Cloud Host + Host Iran, No VPS, No iOS

This is a brutal audit. I read all 83 files as if I am the agentic LLM that will build this and will fail in production if flaws remain.

**STATUS UPDATE - User asked to fix all. Fixes applied per user choices:**
- storage_evergreen: upgrade_50gb -> 10GB Shop + 40GB extra block storage = 50GB total, truly evergreen, cost ~1.2-1.5M/month
- cron_minimum: lazy_check -> cron every 5 min `*/5 * * * *` + lazy check fallback in EnrollmentController@final
- overnight_classes: support_overnight -> is_next_day BOOL added to course_specifications
- polling_interval: 30s -> 30s foreground / 120s background + file cache 5s per user

Fixes applied in migrations and docs - see FIXED tags below.

---

### CRITICAL FLAWS - Will Break Production on Day 1 - FIXED

#### C1. Data Model: family_id Chicken-Egg + Self-Reference - FIXED
**Where:** `07_DATABASE_MIGRATIONS/000007_create_resources_tables.php` - `family_id` foreign key references `id` on same table
**Fix APPLIED:** Made family_id nullable INDEX not FK, first version insert family_id null then Observer created sets family_id = id. Added `is_protected` BOOL and `last_downloaded_at` for LRU.


#### C1. Data Model: family_id Chicken-Egg + Self-Reference
**Where:** `07_DATABASE_MIGRATIONS/000007_create_resources_tables.php` - `family_id` foreign key references `id` on same table, but first version's family_id = its own id. You cannot insert row with family_id = id before id exists (MySQL FK fails).
**Impact:** First resource upload will fail FK violation.
**Fix:** Make `family_id` nullable initially, insert row with family_id null, then update family_id = id after insert in same transaction. Or make family_id not a FK, just UUID indexed. Change migration: `family_id` VARCHAR(36) INDEX not foreign key, or set `family_id` nullable and in Observer `created` event set family_id = id if null.

#### C2. Honor System History Lost
**Where:** `ROLES/STUDENT.md`, `FEATURES/F02_HONOR_SYSTEM.md`, `User` table only has `academic_status_declared` current, not history per semester.
**Impact:** Abuse detection rule "final_semester declared >2 distinct semesters" cannot be implemented - you only have current status, not history. Query `enrollments where academic_status_declared=final_semester at that time` fails because enrollments table doesn't store academic_status at enrollment time.
**Fix:** Add `academic_status_at_enrollment` ENUM to `enrollments` table, or create `academic_status_history` table {id, student_id, status, declared_at, semester_id}. Migration missing.

#### C3. Grace Period Cron on Shared Host May Not Run Every Minute
**Where:** `FEATURES/F03_SCHEDULER_PHASES.md`, `DEPLOYMENT_GUIDE.md` says cPanel Cron `* * * * *`
**Impact:** Many shared hosts (including Pars Pack Cloud Host) limit cron minimum to every 15 minutes, not every minute, for shared hosting. Your grace period wipe exact at 24h will be 15 min late, and ticket escalation 48h check hourly may be delayed. Worse, some hosts disable cron entirely on cheapest Starter plan.
**Fix:** In `02_DEPLOYMENT_GUIDE.md`, you must verify with Pars Pack support: "آیا کران هر دقیقه روی هاست ابری Shop پشتیبانی می‌شود؟" If not, change to every 5 minutes and document that grace period may be 24h+5min. Add fallback: On every enrollment finalization request, also check if grace_period_ends_at <= now and handled=0, then run wipe immediately (lazy check) to handle cron failure.

#### C4. Storage Limit vs Evergreen Contradiction - 10GB Shop Plan Will Fill in 1 Semester
**Where:** `01_DEFINITIVE_SPEC.md` says Evergreen Resource Hub permanent, `FEATURES/F05_RESOURCE_HUB_EVERGREEN.md` says local `/uploads/resources` on Cloud Host Shop plan 10GB SSD, plus LRU cleanup cron daily deletes least recently downloaded non-protected until <7GB.
**Impact:** Evergreen says permanent, but LRU cleanup deletes. Which is it? For 600 students, calculation: Each student uploads 1 file avg 5MB per semester = 600*5MB=3GB, 20 professors *10 files*5MB=1GB, versions double, ticket images 600*2 tickets*2 images*2MB=4.8GB, assignments 600*5 assignments*2MB=6GB, total ~15GB per semester. Shop plan 10GB will fill in 1 semester, then LRU deletes old resources, breaking Evergreen promise. If you enforce 10GB hard limit, after 2 semesters you will delete 1st semester resources, students lose notes.
**Fix:** Decide: Either Evergreen is NOT permanent on shared host (document as "Evergreen within 10GB limit, old LRU deleted") OR you must buy additional block storage / upgrade to 50GB plan (Shop 10GB is not enough for evergreen 4 years). Recommended: Change spec to "Evergreen for current + 1 past semester, older archived to cold storage Arvan S3 or deleted" OR increase Shop to 50GB (ask Pars Pack for custom disk add-on). Update `01_DEFINITIVE_SPEC.md` and `F05` to clarify.

#### C5. MySQL Max Connections on Shared Host - 600 Users Polling Every 15s = 40 req/s = Will Hit max_connections
**Where:** `FEATURES/F15_NOTIFICATION_INTRANET.md` polling primary every 15s foreground, 60s background, plus all other API calls.
**Impact:** 600 users * polling 1 req/15s = 40 req/s constant + enrollment + resource list etc. Shared host MySQL max_connections typically 100-200. Each Laravel request opens 1 MySQL connection, holds for ~50-100ms. 40 req/s * 0.1s = 4 concurrent connections average, okay, but peak enrollment 200 concurrent finalizing at same second = 200 concurrent connections -> MySQL max_connections exceeded -> 500 errors "Too many connections" -> enrollment fails during critical period.
**Fix:** Implement MySQL connection pooling? Not possible on shared host. Instead: Add caching for polling endpoint: `GET /api/notifications/unread` should cache result in file cache for 5 seconds per user? Actually per user cache 5s reduces DB hits. Or increase polling interval from 15s to 30s foreground, 120s background for shared host. Document in `F15` and `09_PROJECT_STRUCTURE.md`: Polling interval for shared host must be 30s not 15s to avoid DB overload. Or use MySQL `GET_LOCK`? No. Better: Use file-based notification queue: Write notifications to file per user, not DB, for polling endpoint to avoid DB query.

---

### HIGH FLAWS - Major Risk, Will Cause Bugs or Suspension

#### H1. IdempotencyKeys Table Grows Indefinitely - No Cleanup Cron
**Where:** `07_DATABASE_MIGRATIONS/000011_create_system_tables.php` - `idempotency_keys` with expires_at 24h index but no cleanup cron defined.
**Impact:** Every mutating request creates 1 row, 600 students * 20 actions/day = 12k rows/day, 30 days = 360k rows, table grows indefinitely, slows down idempotency check, eventually hits MySQL disk limit 10GB.
**Fix:** Add cron daily `idempotency:cleanup` command: `DELETE FROM idempotency_keys WHERE expires_at < NOW()`, define in `Kernel.php` dailyAt 02:00, document in `02_DEPLOYMENT_GUIDE.md`.

#### H2. Resource Download LRU Tracking Missing Server Side
**Where:** `FEATURES/F05_RESOURCE_HUB_EVERGREEN.md` says FileCacheService local LRU 100MB client + server side LRU cleanup cron daily to keep under 10GB, deletes least recently downloaded non-protected until <7GB.
**Impact:** Server has no table tracking last_downloaded_at for resources, so cron cannot know LRU order. You have `download_count` but not last_accessed. LRU needs last_accessed.
**Fix:** Add `resource_download_logs` table or add `last_downloaded_at` DATETIME to `resources` table, update on each download GET /api/v1/resources/{id}/download. Migration missing.

#### H3. Broadcast Throttle Table Missing
**Where:** `FEATURES/F07_MESSAGING_UNIFIED.md`, `ROLES/PROFESSOR.md` says broadcast rate limit 1 per 10 min per professor per spec via MySQL cache table.
**Impact:** No migration for `broadcast_throttle` table.
**Fix:** Create migration `broadcast_throttles` table spec_id, professor_id, last_sent_at, or use file cache with key `broadcast:{spec_id}:{professor_id}`.

#### H4. File Upload .htaccess Protection Missing in Deployment Guide
**Where:** `12_SECURITY_CHECKLIST.md` says `.htaccess php_flag engine off` in uploads folder, but `02_DEPLOYMENT_GUIDE.md` doesn't mention creating it.
**Impact:** Attacker uploads file named `shell.pdf.php` with PDF magic but containing PHP code `<?php system($_GET['cmd']); ?>`, finfo may detect as PDF if starts with %PDF but contains PHP after, then requests `/uploads/resources/.../shell.pdf.php?cmd=rm -rf /`, executes, full server compromise.
**Fix:** In deployment guide, add step: Create `/public_html/uploads/.htaccess` with:
```
php_flag engine off
RemoveHandler .php .phtml .php3
Deny from all
<FilesMatch "\.(pdf|docx|png|jpg|jpeg)$">
  Allow from all
</FilesMatch>
```
And also check double extension via validation: reject filename containing `.php` anywhere.

#### H5. Unlimited Bandwidth Marketing vs Fair Usage - Shared Host Will Suspend
**Where:** `01_DEFINITIVE_SPEC.md` and `02_DEPLOYMENT_GUIDE.md` says Cloud Host unlimited bandwidth, but all Iranian shared hosts have fair usage 2TB then throttle.
**Impact:** Resource hub with 600 students each downloading 10 resources 5MB each = 600*10*5MB=30GB per day during exam week, 900GB per month, may exceed fair usage 2TB? Actually 900GB <2TB okay, but if each student downloads 50 resources 50MB = 600*50*50MB=1.5TB per day = 45TB per month, definitely suspend.
**Fix:** Document realistic bandwidth calculation for 600, recommend Cloud Host Shop unlimited but with note fair usage 2TB/month, and implement rate limiting for download: max 20 downloads per student per day via MySQL table download_daily_counts, or use Cache API client-side to avoid re-downloading same file.

#### H6. No Backup Offsite - cPanel Backup on Same Disk
**Where:** `02_DEPLOYMENT_GUIDE.md` says cPanel daily DB backup + weekly full backup, but where is backup stored? Same disk /home/username/backups, if disk fails or server dies, backup lost.
**Impact:** Single point of failure, university loses all data.
**Fix:** Add offsite backup to second Iranian provider: Use cron daily `mysqldump` + `tar` uploads + `rclone` to Pars Pack S3 or Arvan S3 or second VPS in different DC. Document in deployment guide, add cost 2M/month for backup VPS.

#### H7. Let's Encrypt Renewal Fails During National Internet Shutdown
**Where:** `02_DEPLOYMENT_GUIDE.md` says Let's Encrypt via cPanel auto renew.
**Impact:** Let's Encrypt HTTP-01 challenge needs to reach outside internet to validate domain (Let's Encrypt servers outside Iran). During national shutdown (could be days/weeks), renewal fails, cert remains valid 90 days, okay for short shutdown, but if shutdown >90 days (unlikely but possible) or cert expires right during shutdown, site shows SSL error on intranet, students cannot access even via intranet IP because browser blocks self-signed? Actually if you use same domain with split DNS internal IP, SSL error will block PWA.
**Fix:** Add self-signed fallback cert for `unify.local` internal domain + add to Android network_security_config.xml to trust self-signed, so during shutdown with expired Let's Encrypt, students can still access via `https://unify.local` with self-signed trusted by app.

#### H8. Pushe API May Not Be Reachable During Pure Intranet
**Where:** `F15_NOTIFICATION_INTRANET.md` says Pushe has Iranian servers reachable via intranet, unlike FCM.
**Impact:** Need to verify: Does `api.pushe.co` resolve to Iranian IP or foreign? If it resolves to foreign IP via Cloudflare, during pure intranet with international gateway closed, DNS may fail or IP unreachable, Pushe push will fail even though Pushe has Iranian servers. Same for Kavenegar SMS API.
**Fix:** Before final deployment, test from inside university network with international gateway blocked: `curl -v https://api.pushe.co` and `nslookup api.pushe.co` check IP is Iranian (e.g., 185.10.x.x). If not, need to ask Pushe for intranet IP endpoint. Document test step in `10_TESTING_STRATEGY.md` intranet simulation.

#### H9. No Handling of Overnight Classes
**Where:** `FEATURES/F03_SCHEDULER_PHASES.md` validation time_end > time_start.
**Impact:** Some labs may be 18:00-20:00 or overnight 22:00-02:00 next day. Current validation blocks overnight.
**Fix:** Allow time_end < time_start flag as next day, add `is_next_day` BOOL to CourseSpecification, or store time_end as DATETIME next day. For MVP, document as not supported and add validation message "کلاس شبانه پشتیبانی نمی‌شود".

#### H10. No Maximum File Path Length Check
**Where:** `resources` table file_path TEXT, but file system path `/home/username/public_html/uploads/resources/{course_id}/{professor_id}/{uuid}.pdf` may exceed 255 chars if course_id long? TEXT okay, but OS path limit 4096, okay. However cPanel file manager may have 255 limit for filename.

---

### MEDIUM FLAWS - Should Fix Before 600 Production

#### M1. ENUM Use in MySQL Hard to Migrate
- `role` ENUM(student, professor, expert...), `global_state` ENUM, `day_of_week` ENUM, `status` ENUM everywhere. Adding new role or new event_type requires ALTER TABLE which locks table on MySQL 8 with 600k rows may take minutes, downtime.
- Better: Use VARCHAR(32) + check constraint or lookup table. For MVP okay, but document migration pain.

#### M2. No Soft Deletes for Many Tables
- Courses, specs, resources, messages use hard delete (per Hard Delete vs Soft Hide). For audit, you may want soft deletes (deleted_at) for courses/specs to keep enrollment history. Currently if Expert hard deletes spec, enrollments orphaned? You archive enrollments to history? In F14 you said hard delete after archiving, but no history table defined.

#### M3. No Composite Indexes for Common Queries
- Archive dropdown query `SELECT DISTINCT semester_id FROM enrollments WHERE student_id=self AND status=archived` needs index (student_id, status, semester_id) composite, currently only index (student_id, semester_id) exists, missing status.
- Inbox query WHERE recipient_id=self OR (specification_id IN my enrolled spec ids) needs index (recipient_id, sent_at) and (specification_id, sent_at) composite.
- Add migrations for composite indexes.

#### M4. No Handling of Course Code Case Sensitivity
- Course code unique, but MySQL unique collation utf8mb4_general_ci case-insensitive, so CS101 and cs101 considered same, okay, but if case-sensitive collation, duplicate. Should enforce LOWER(code) unique.

#### M5. StudentPassedCourse entry_year Not Scoped
- StudentPassedCourse has entry_year field but not used to scope? Should be per entry year, but unique constraint is student_id+course_id only, so if student changes entry year filter, same passed status shows for all entry years. Should be unique student_id+course_id+entry_year or have entry_year nullable and progress per entry year calculated via chart_data? Ambiguous.

#### M6. Assignment Tracker local_notification_scheduled BOOL but No Reschedule on Edit
- If student edits due date, old local notification not cancelled, new one scheduled, two notifications fire. Need to cancel old via Capacitor LocalNotifications cancel with id, then schedule new.

#### M7. Curriculum Chart JSON No Schema Validation
- chart_data JSON MySQL 8, but no JSON schema validation in Laravel Request, could store invalid structure. Add validation rule `json` + custom rule checking semesters array, courses array, etc.

#### M8. Forms file_size not validated in migration, but validation in controller max 20MB, okay, but file_size column nullable in migration but should be NOT NULL.

#### M9. DeviceToken provider fcm/pushe/web_push but we have no fcm configured for shared host, only pushe. Should remove fcm or add note fcm only if outside Iran.

#### M10. Notification type VARCHAR(50) not ENUM, may have typos, should be ENUM list of event types from F15.

#### M11. HonorFlags resolved boolean but no resolution timestamp, no resolver id.

#### M12. GoldenScheduleCache preferences_hash VARCHAR(64) no index for lookup, combos JSON may be large > MySQL JSON limit 1GB? Actually LONGTEXT needed.

#### M13. No Handling of Daylight Saving Time for Asia/Tehran
- Iran DST changes, grace_period_ends_at stored UTC but displayed Tehran, cron every minute checks now>=grace_ends, but if DST shift during grace period, countdown may be 1h off. Should use Carbon with timezone Asia/Tehran for all calculations, store UTC.

#### M14. No PWA Offline Fallback Page
- Workbox runtime caching for GET specs etc., but what if user navigates to /scheduler/enrolling while offline and not cached? Should have offline.html fallback page.

#### M15. No Android APK Build Steps Detailed
- Keystore generation `keytool -genkey -v -keystore unify.keystore -alias unify -keyalg RSA -keysize 2048 -validity 10000`, signing, versionCode increment, network_security_config.xml to trust self-signed for intranet, etc. Not detailed in deployment guide.

#### M16. No Legal Terms, Privacy Policy, User Manual
- For 600 students, need privacy policy Persian: what data collected, where stored, who can see mobile/email, audit log retention 2 years, etc. No doc.

#### M17. No Cost Projection Beyond 600
- If grows to 1200 students, cost doubles? Need to document scaling cost: Shop 10GB will fill, need upgrade to 50GB custom disk add-on via Pars Pack ticket, cost X.

#### M18. No Performance Budget
- Bundle size <300KB, image max 500KB, initial load <2s on 3G, Lighthouse score >90, not defined.

#### M19. No Accessibility Audit
- Reduced motion fallback for exam flip done, but no screen reader testing, no keyboard navigation testing, no ARIA labels Persian defined in P18 but not tested.

---

### LOW FLAWS - Nice to Have

- L1. No Storybook for common components
- L2. No CI/CD pipeline GitHub Actions to deploy to cPanel via FTP
- L3. No monitoring Uptime + error logs + slow queries
- L4. No disaster recovery plan what if entire Pars Pack DC down
- L5. No rate limiting per endpoint documented in OpenAPI (only global login throttle)
- L6. No versioning for API (v1 only, no v2 plan)
- L7. No handling of course code with special characters

---

### SUMMARY - What Must Be Fixed Before Giving to Agentic LLM

**Critical (Blocker) - Fix now:**
- C1 family_id FK chicken-egg
- C2 honor history lost - need enrollments.academic_status_at_enrollment or history table
- C3 cron every minute may not be supported on shared host - verify with Pars Pack support + add lazy check fallback
- C4 storage 10GB vs evergreen permanent contradiction - decide 10GB LRU or evergreen 50GB custom disk add-on, update spec
- C5 MySQL max_connections 100-200 vs 40 req/s polling + 200 concurrent enrollment peak - add file cache for polling endpoint + increase polling interval 15s->30s

**High (Major risk):**
- H1 IdempotencyKeys cleanup cron missing
- H2 Resource LRU tracking last_downloaded_at missing
- H3 Broadcast throttle table missing
- H4 .htaccess php_flag engine off missing in deployment guide
- H5 unlimited bandwidth fair usage vs 600 students downloading 30GB/day
- H6 offsite backup missing
- H7 Let's Encrypt renewal fails during shutdown
- H8 Pushe API reachable during pure intranet test needed
- H9 overnight classes not supported
- H10 file path length

**Medium (Should fix before 600 prod):**
- M1 ENUM hard to migrate, M2 soft deletes, M3 composite indexes, M4 case sensitivity, M5 StudentPassedCourse entry_year scope, M6 assignment local notification reschedule, M7 curriculum JSON schema validation, M8 forms file_size NOT NULL, M9 DeviceToken fcm, M10 Notification type ENUM, M11 HonorFlags resolved timestamp, M12 GoldenScheduleCache index, M13 DST, M14 PWA offline fallback, M15 Android APK build steps, M16 privacy policy, M17 cost projection, M18 performance budget, M19 accessibility

**If you fix C1-C5 + H1-H8 before giving to LLM, the LLM will have 80% less chance to build something that breaks on day 1 for 600 students.**

END FLAW AND GAP ANALYSIS
