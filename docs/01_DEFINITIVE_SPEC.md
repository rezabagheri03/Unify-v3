# Unify V9 Definitive - Shared Host Ready - Pars Pack Cloud Host + Host Iran
## For 600 Computer Engineering Students - No VPS

### 0. Glossary (Same as V7, clarified for MySQL)
- Course Specification: Course + Professor + Day + Time + Location. No capacity.
- Evergreen Resource Hub: Linked to (course_id, professor_id), not semester. Permanent. Old versions hard delete file content after 30 days via cron, but ratings/sticky preserved via family_id.
- Hard Delete vs Soft Hide: Hard Delete = file + row deleted. Soft Hide = is_active=false for old semester specs. This implements "no archive mode + background state" via visibility flag, no second DB.
- Shamsi Canonical: Stored as Gregorian DATETIME in MySQL (UTC), displayed YYYY/MM/DD Jalali via Morilog\Jalali in PHP + date-fns-jalali in React. Original Shamsi input stored as shamsi_original VARCHAR(10).

### 1. Context & Stack V9

**Context:** Persian RTL university assistant, 600 CS students, must work during National Intranet, must be deployable on Pars Pack Cloud Host (https://parspack.com/host/cloud-host) + Host Iran (https://parspack.com/host/iran) - both inside Iran, SHOMA connected, unlimited bandwidth.

**Stack V9 - Shared Host Compatible:**

**Frontend (Unchanged):**
- React 18 + TypeScript + Vite + PWA (Workbox)
- Zustand + idb-keyval (IndexedDB) for local cache, no SQLite
- MUI v5 RTL or Tailwind, Framer Motion exam flip with reduced-motion fallback
- Built as static `dist/` - upload to `public_html` or `public_html/frontend`

**Backend (Changed from FastAPI Python to PHP Laravel) - FIXED per User Choices:**
- PHP 8.2 + Laravel 10
- MySQL 8 (unlimited databases on Cloud Host)
- File Storage: Local filesystem `/public_html/uploads/resources`, `/uploads/forms`, `/uploads/branding` - not MinIO. **FIX C4: Now 50GB total (Shop 10GB + 40GB extra block storage add-on via Pars Pack ticket) for truly evergreen permanent, no LRU deletion needed for 4 years. For safety, still keep LRU cleanup cron daily as fallback but with 50GB limit: delete least recently downloaded non-protected only if size >40GB (80% of 50GB) until <35GB.** Protected professor files never auto-evicted (is_protected=1).
- Cache: File cache + MySQL cache driver + Memcached (available on Cloud Host) - no Redis needed. For idempotency keys, use MySQL table `idempotency_keys` with daily cleanup cron `idempotency:cleanup` (FIX H1).
- Queue / Jobs: No Celery. Use Laravel Scheduler + cPanel Cron **FIX C3: Changed from every minute `* * * * *` to every 5 minutes `*/5 * * * *` because many shared hosts limit cron minimum to 15 min on Starter, but Shop allows 5 min. Plus lazy check fallback: On every enrollment finalize request, also check if grace_period_ends_at <= now and handled=0, then run wipe immediately.** Jobs: grace period wipe (every 5 min + lazy check), ticket escalation (hourly), calendar warnings (daily), file cleanup (daily), old version hard delete (daily), idempotency cleanup (daily 02:00), download daily counts cleanup
- Real-time: No WebSocket. Use Polling: Frontend **FIX C5: Changed from 15s foreground / 60s background to 30s foreground / 120s background + file cache 5s per user for polling endpoint** to reduce MySQL max_connections load (600 users polling 15s = 40 req/s -> 30s = 20 req/s, half). File cache: `GET /api/notifications/unread` caches result in file cache for 5 seconds per user via `Cache::put("notifications:{$user_id}", $result, 5)`. For Android push, backend calls Pushe API via HTTP (curl) - Pushe servers inside Iran, works even if you are on shared host, you just need API key from env.
- Auth: Laravel Sanctum (SPA auth with cookie) or JWT via tymon/jwt-auth. Argon2id via PHP `password_hash(..., PASSWORD_ARGON2ID)` - same security as V7.
- Excel: PhpSpreadsheet (instead of openpyxl)
- Shamsi: Morilog\Jalali PHP library + date-fns-jalali JS
- SMS: Kavenegar API via HTTP (optional for critical alerts)

**Hosting - FIXED per User Choice: 50GB Upgrade for Evergreen:**
- Cloud Host: https://parspack.com/host/cloud-host - Plans: Starter 1GB SSD/1vCPU/2GB RAM 229,500 Toman, Startup 5GB/3vCPU/4GB RAM 341,600-420,750 Toman, Shop 10GB/5vCPU/7GB RAM 588k-716,550 Toman - All unlimited bandwidth, unlimited DB/email/subdomain/FTP, daily DB backup, weekly full backup, free Let's Encrypt SSL, LiteSpeed, LiteSpeed cache, Memcached, SSH on cPanel, PHP management, auto upgrade
- **FIX C4: Storage vs Evergreen - User selected upgrade to 50GB custom disk add-on via Pars Pack ticket for truly evergreen permanent (not LRU).** Shop 10GB will fill in 1 semester (600*5 files*3MB=9GB + ticket images 4.8GB + assignments 6GB = ~20GB/semester). For 600 CS students 4 years evergreen needs ~60GB. **Recommended: Shop 10GB + 40GB extra block storage add-on via ticket to Pars Pack support - total 50GB, cost ~1.2-1.5M Toman/month, truly evergreen permanent, no LRU deletion.** Alternative: Keep 10GB hot + archive old to Arvan S3 cold storage via rclone. This doc now assumes 50GB total (10GB base + 40GB add-on) for evergreen.
- Host Iran: https://parspack.com/host/iran - Same as Cloud Host but Iran location low ping, half-price domestic traffic, good for frontend static
- Recommended: Buy ONE Cloud Host Shop + 40GB extra block storage = 50GB total for backend API + MySQL + files + frontend static in same host (simplest, truly evergreen). Or buy TWO: Host Iran for frontend static (low ping) + Cloud Host for backend API (more CPU). For 600 students one Shop+40GB=50GB is enough for 4 years.

**Non-Negotiables Kept:**
- Honor System stays (self-declared academic_status)
- Intranet must-have stays via polling (HTTP works on SHOMA, WebSocket not needed)
- IT handout username/password physical stays

### 2. Shamsi Date Handling V9 (MySQL)
- MySQL: `DATETIME` UTC + `shamsi_original VARCHAR(10)` for trace
- PHP: Morilog\Jalali `Jalalian::fromFormat('Y/m/d', $shamsi)->toCarbon()` to Gregorian, `Jalalian::fromCarbon($gregorian)->format('Y/m/d')` to Shamsi
- API sends both `gregorian_iso` and `shamsi_formatted`
- React: date-fns-jalali for display
- Validation: Jalali check `Jalalian::isValid`

### 3. Data Model V9 (MySQL, not PostgreSQL)

**User**
- id VARCHAR(32) PK Student Number / Personnel ID
- password_hash TEXT Argon2id
- first_name VARCHAR(100) NOT NULL after onboarding
- last_name VARCHAR(100) NOT NULL after onboarding
- role ENUM(student, professor, expert, head_of_dept, admin, owner)
- department_id VARCHAR(32) FK nullable for student, NOT NULL staff, INDEX
- academic_status_declared ENUM(normal, conditional, gpa_a, final_semester) - HONOR FIELD
- academic_status_last_declared_at DATETIME, academic_status_declaration_count INT default 0, is_honor_system_acknowledged BOOL
- created_at DATETIME, is_banned BOOL default 0, banned_reason TEXT, banned_at DATETIME, banned_by VARCHAR(32)
- supplementary_details TEXT, mobile VARCHAR(20) nullable not visible staff, email VARCHAR(255) nullable not visible staff
- must_change_password BOOL default 1, temporary_password_expires_at DATETIME (7 days), last_login_at DATETIME
- Indexes role, department_id, is_banned

**Department** id VARCHAR(32) PK, name_fa TEXT, name_en TEXT

**Semester** id VARCHAR(32) PK, name VARCHAR(100), is_current TINYINT(1) INDEX, global_state ENUM(enrolling, active, exam), start_date_g DATETIME, end_date_g DATETIME, shamsi_original_start/end VARCHAR(10), grace_period_ends_at DATETIME nullable, grace_period_handled BOOL default 0

**Course** id VARCHAR(32) PK, code VARCHAR(32) UNIQUE, name TEXT, credits TINYINT, department_id VARCHAR(32) FK, is_active BOOL

**CourseSpecification - FIXED day_of_week + overnight support (User selected support overnight)**
- id VARCHAR(32) PK, course_id VARCHAR(32) FK, professor_id VARCHAR(32) FK, day_of_week ENUM(sat, sun, mon, tue, wed, thu, fri) NOT NULL, time_start TIME, time_end TIME, is_next_day BOOL default 0 (FIX H9: If true, time_end is next day, e.g., 22:00-02:00 overnight lab, handled as two blocks 22:00-24:00 today + 00:00-02:00 next day), location VARCHAR(255), telegram_link TEXT, exam_date_final_g DATETIME, shamsi_original_final VARCHAR(10), exam_date_midterm_g DATETIME, shamsi_original_midterm VARCHAR(10), is_active BOOL default 1, semester_id VARCHAR(32) FK INDEX, created_at DATETIME, Indexes (course_id, professor_id), (semester_id, is_active), professor_id, day_of_week, CHECK (is_next_day=0 AND time_end > time_start) OR (is_next_day=1)

**StudentPassedCourse** id CHAR(36) PK UUID, student_id VARCHAR(32) FK, course_id VARCHAR(32) FK, passed BOOL, grade FLOAT nullable, entry_year INT, created_at DATETIME, UNIQUE(student_id, course_id, entry_year) FIX M5: Unique per entry_year for progress per entry year + index (student_id, entry_year)

**Enrollment** id CHAR(36) PK UUID, student_id VARCHAR(32) FK INDEX, specification_id VARCHAR(32) FK, semester_id VARCHAR(32) FK INDEX, status ENUM(temporary, finalized, archived), academic_status_at_enrollment ENUM(normal, conditional, gpa_a, final_semester) nullable FIX C2: Store status at enrollment time for abuse detection final_semester >2 distinct semesters, enrolled_at DATETIME, finalized_at DATETIME nullable, version INT, UNIQUE(student_id, specification_id, semester_id), INDEX (student_id, status, semester_id) FIX M3 composite for archive dropdown

**Resource - MySQL version - FIXED C1 + H2**
- id CHAR(36) PK, course_id VARCHAR(32) FK, professor_id VARCHAR(32) FK, specification_id VARCHAR(32) FK nullable, uploader_id VARCHAR(32) FK, title VARCHAR(255), description TEXT, file_path TEXT (max 255 validation, TEXT for OS path limit 4096) FIX H10: Validate filename max 255, no .php anywhere, file_path TEXT, file_size_bytes BIGINT CHECK <=52428800, file_mime VARCHAR(50) CHECK pdf/docx via finfo, shamsi_original VARCHAR(10), created_at_g DATETIME, status ENUM(pending, approved, rejected), version INT default 1, previous_version_id CHAR(36) FK self nullable, family_id CHAR(36) nullable INDEX FIX C1: family_id nullable initially to avoid chicken-egg FK violation, first version insert family_id null then Observer created event sets family_id = id, scheduled_hard_delete_at DATETIME nullable, average_rating FLOAT default 0, rating_count INT default 0, download_count INT default 0, last_downloaded_at DATETIME nullable FIX H2: LRU tracking for 50GB upgrade, badge_type ENUM(professor, expert_approved, admin_approved) nullable, is_superseded BOOL default 0, is_deleted_content BOOL default 0, is_protected BOOL default 0 (professor badge never auto-evicted)

**ResourceRating** id CHAR(36) PK, student_id+resource_family_id UNIQUE, rating TINYINT 1-5, rated_at DATETIME, is_self_rating BOOL

**ResourceStickyNote** id CHAR(36) PK, student_id+resource_family_id UNIQUE, note TEXT max 1000, created_at, updated_at

**Message** id CHAR(36) PK, sender_id FK, recipient_id FK nullable, specification_id FK nullable, subject VARCHAR(255), body TEXT, sent_at DATETIME, is_edited BOOL, edited_at nullable, is_deleted BOOL default 0, deleted_at nullable, parent_message_id CHAR(36) FK self nullable, priority ENUM(low, normal, high), Indexes recipient_id, specification_id, sent_at

**MessageReadStatus** id CHAR(36) PK, message_id FK, user_id FK, read_at DATETIME, UNIQUE(message_id, user_id)

**Ticket** id CHAR(36) PK, student_id FK, department ENUM(education, technical, student_affairs), subject VARCHAR(255), description TEXT, status ENUM(open, in_progress, answered, closed) default open, assigned_to VARCHAR(32) FK nullable, student_attachments JSON (max 3 images 5MB each), staff_attachments JSON, created_at, updated_at, closed_at nullable, escalated_at nullable, is_escalated BOOL default 0, escalation_level INT default 0

**TicketReply** id CHAR(36) PK, ticket_id FK, sender_id FK, body TEXT, attachments JSON, sent_at DATETIME, is_staff BOOL

**AssignmentTracker** id CHAR(36) PK, student_id FK, specification_id FK, title VARCHAR(255), description TEXT, due_date_g DATETIME, shamsi_original VARCHAR(10), reminder_before_hours INT default 24, status ENUM(pending, submitted, graded, late, missed), attachment_path TEXT nullable, grade FLOAT nullable, graded_by FK nullable, graded_at nullable, submitted_at nullable, created_at DATETIME, local_notification_scheduled BOOL

**CurriculumChart** id CHAR(36) PK, department_id FK, entry_year INT, chart_data JSON, status ENUM(draft, pending_approval, approved), approver_id FK nullable, approved_at DATETIME, version INT, UNIQUE(department_id, entry_year, version)

**FAQ, NoticeBoard, Form, AcademicCalendar** same as V7 but MySQL types, JSON stored as JSON type MySQL 8, file_path TEXT, etc.

**DeviceToken** id CHAR(36) PK, user_id FK, token TEXT, provider ENUM(fcm, pushe, web_push), platform ENUM(web, android), is_active BOOL, last_used_at

**AuditLog** id CHAR(36) PK, user_id FK nullable, action ENUM(deletion, major_edit, password_reset, role_change, ban, honor_status_change, final_semester_abuse_flag, login, failed_login), resource_type VARCHAR(50), resource_id VARCHAR(100), timestamp DATETIME default now, ip_address VARCHAR(45), user_agent TEXT, details JSON (encrypted via Laravel Crypt::encryptString with APP_KEY), is_suspicious BOOL

**IdempotencyKeys** id CHAR(36) PK, key VARCHAR(36) UNIQUE, user_id FK, response_code INT, response_body JSON, created_at DATETIME, expires_at DATETIME (24h) - replaces Redis

**Notification** id CHAR(36) PK, user_id FK, type VARCHAR(50), title VARCHAR(255), body TEXT, data JSON, priority ENUM(critical, high, low), read BOOL default 0, created_at DATETIME

**NotificationMute** user_id+specification_id UNIQUE, muted BOOL

### 4. Cross-Platform Architecture V9 - No VPS

**Frontend PWA:**
- React 18 + Vite PWA + Workbox
- State Zustand + idb-keyval (IndexedDB)
- No capacitor-sqlite, no SQLCipher
- File cache via Cache API + Workbox LRU 100MB (eviction via Workbox)
- No iOS native

**Backend Laravel:**
- Laravel 10 + PHP 8.2
- No Docker needed for shared host, but Docker for local dev okay
- No separate WebSocket server - polling endpoint GET /api/notifications/unread?since=
- No MinIO - local filesystem
- No Celery - Laravel Scheduler + Cron

**Directory Structure V9 (Shared Host):**
```
/ (cPanel home /home/username)
  /public_html (Laravel public + React build)
    /index.html (React PWA)
    /assets (React JS/CSS)
    /uploads/resources/{course_id}/{professor_id}/{uuid}.pdf
    /uploads/forms/{dept}/{uuid}.pdf
    /uploads/branding/logo.png
    /api (Laravel public/index.php handles /api/* via .htaccess)
  /unify-backend (Laravel app outside public_html for security)
    /app
    /config
    /database/migrations
    /routes/api.php
    /storage
  / (React source /src for local dev, not on host)
```

**Local Dev:**
- XAMPP/Laragon or Docker: PHP 8.2 + MySQL 8 + Composer + Node 18
- No Redis needed

### 5. Scheduler V9 (PHP version of V7, honor kept)

Same logic as V7 but PHP:

- Honor checkboxes + acknowledge required, stored academic_status_declared + count + last_declared + is_honor_acknowledged
- Abuse detection: final_semester >2 distinct semesters -> flag final_semester_abuse_flag + notify Expert
- Time overlap: day_of_week == day_of_week AND time intervals overlap
- Exam overlap: same day Gregorian with 2h buffer
- Prereq: Check StudentPassedCourse, warn modal, allow continue (honor)
- Coreq: Allow, warn if missing
- Golden Scheduler: Backtracking with MRV heuristic in PHP, timeout 5 sec server, max 1000 combos, scoring freeDays*20 -gap*10 +profBonus*15 -daysWithClasses*5, return top 15, cached in MySQL table GoldenScheduleCache
- Grace Period: Admin switches enrolling->active sets grace_period_ends_at=now+24h Asia/Tehran, Cron every minute checks if now>=grace_ends and handled=0 -> hard delete temporary enrollments, notify affected

### 6. API Contract V9 (Laravel, polling, MySQL idempotency)

**Auth:**
- POST /api/v1/auth/login {username, password} -> Sanctum token + user, rate limit 5/min per IP via Laravel throttle middleware, failed logs AuditLog failed_login
- POST /api/v1/auth/refresh? Actually Sanctum uses cookie, no refresh rotation needed, or JWT via tymon/jwt-auth
- POST /api/v1/onboarding {first_name, last_name}
- POST /api/v1/password/change {old, new} complexity min 8 1 special 1 number not same last 3

**Scheduler:** Same endpoints as V7, but MySQL idempotency: Client sends Idempotency-Key header UUID, server checks IdempotencyKeys table (key, user_id, expires 24h) if exists returns previous response, else processes and stores

**Resources:** Same, but download returns direct file path `/uploads/resources/...` not signed S3 URL, increments download_count

**Messaging:** Same as V7, broadcast via single row spec_id, read status table

**Sync:** No full SyncQueue SQLite, only IndexedDB queue for 5 safe types: rating, sticky, ticket create/reply, assignment, curriculum checkbox. Background sync every 2 min when online via Workbox Background Sync + setInterval. For enrollment final, requires online.

### 7. Resource Hub V9 (Local filesystem, 10GB limit)

- Evergreen (course_id, professor_id) kept
- Types PDF/DOCX only magic bytes check via PHP finfo
- Max 50MB, quota 5/day via MySQL count + Redis? No Redis, use MySQL table ResourceUploadCount user_id date count
- Server storage local, not S3, client Cache API 100MB LRU via Workbox, professor protected never evicted (pin), user can pin
- Upload flow: Student upload -> temp folder, create Resource row pending, notify approvers via polling + Pushe API curl if Android, on approve move file from temp to permanent `/uploads/resources/{course}/{prof}/{uuid}.pdf`, status approved
- Versioning: family_id, previous_version_id, is_superseded, scheduled_hard_delete_at now+30d, Cron daily deletes file content, keeps row with file_path null and is_deleted_content=1, ratings via family_id preserved
- Rating: Optional snackbar after 30s, POST rating replaces old, average excludes self (is_self_rating false), distribution, self rating allowed flagged
- Sticky: Private, encrypted via Laravel Crypt::encryptString

### 8. Semester Lifecycle V9 (Same, MySQL)

- Define new semester via Admin page, transaction: old is_current false, old specs is_active false, old finalized enrollments -> archived, old temp hard deleted, resources untouched, notification via polling + Pushe
- Archive dropdown UI student top, read-only

### 9. Notification V9 (Polling + Pushe PHP, No WebSocket)

**Must-have intranet kept via polling:**

- Primary: Polling GET /api/notifications/unread?since=last_timestamp every 15s foreground, 60s background via setInterval. Works on intranet because HTTP to Iranian IP works even when international cut.
- Android Intranet Push: Server calls Pushe API via PHP curl `https://api.pushe.co/v2/...` with API key from env `PUSHE_API_KEY` - Pushe has Iranian servers, works intranet. For each notification event, check DeviceToken where provider=pushe and is_active and user_id in target, send via Pushe.
- iOS: Removed entirely (as per user request)
- No internal WebSocket server (was separate service) - removed to fit shared host
- No FCM/APNs needed for MVP, but can add FCM for outside intranet via same PHP curl to FCM API
- Mute per spec via NotificationMute table
- Priority: critical (spec time change, conflict, grace ending <2h) -> polling + Pushe + SMS optional Kavenegar, high (new resource, ticket answered, registration warnings, assignment deadline, graded) -> polling + Pushe, low (general) -> polling only
- Local notifications: Capacitor LocalNotifications scheduling for assignment deadlines, still works because frontend schedules locally via Capacitor, no server needed

### 10-12. Client, Admin Panels, RBAC - Same business logic, technical notes PHP

- Student, Professor, Expert, Head, Admin, Owner pages same as V7, but all API calls now Laravel
- RBAC matrix same as V7, enforced via Laravel middleware + Policies + row-level dept scope, not trusting client
- Professor broadcast rate limit 1 per 10 min via MySQL cache table

### 13. Security & IT Handout V9 (PHP)

- IT handout kept: Bulk import generates temp password 12 chars via `Str::random(12)`, hashed Argon2id via `Hash::make` with driver argon2id, must_change_password=1, expires 7 days, envelope PDF generated on-fly via dompdf, not stored, forced change onboarding
- Rate limiting via Laravel throttle (no Redis, uses MySQL cache)
- JWT/Sanctum: Sanctum SPA with httpOnly cookie + CSRF
- AuditLog details encrypted via `Crypt::encryptString` with APP_KEY
- File upload magic bytes via finfo, block exe

### 14. Excel Import/Export V9 (PhpSpreadsheet)

- Same transactional: BEGIN, validate all rows, if any error ROLLBACK + error report Excel with column خطا, no partial
- Row limit 2000, file size 5MB, MIME check, Shamsi validation via Morilog\Jalali, Time HH:MM, Telegram URL https://t.me/
- Templates same as V7 but MySQL

### 15-20. Other Features Same Logic, PHP Implementation Notes

- Curriculum Charts: Tree JSON MySQL, draft->pending->approved, OR merge for passed checkbox via IndexedDB
- Assignment Tracker: Due date Gregorian + shamsi_original, reminder_before_hours, status pending/submitted/graded/late/missed, late detection via cron hourly, local notification via Capacitor, grade notification via polling + Pushe
- Academic Calendar: Timeline, color per event_type, 7-day 24h warnings via cron daily
- Theme Branding: Logo upload to `/uploads/branding`, SystemConfig table brand_name default Unify
- Exam Flip: Same Framer Motion reduced motion fallback
- Grace Period: Cron every minute

### 21. Deployment V9 - Pars Pack Cloud Host + Host Iran

**Recommended Plan for 600 Students:**
- **Shop: 10GB SSD / 5 vCPU / 7GB RAM / Unlimited Bandwidth = 588k-716,550 Toman/month** - 7GB RAM, 5 cores, enough for 200 concurrent enrollment peak
- Start with Startup 5GB/3vCPU/4GB RAM 341,600 Toman for MVP, upgrade in panel zero downtime

**Why Cloud Host is okay for 600:**
- Unlimited bandwidth monthly, unlimited DB/email/subdomain/FTP, daily DB backup + weekly full backup, free Let's Encrypt SSL, LiteSpeed + LiteSpeed Cache, Memcached available, SSH on cPanel, PHP management, auto upgradable

**Deployment Steps (cPanel):**
1. Buy Cloud Host Shop or Startup from https://parspack.com/host/cloud-host location Iran
2. Buy domain .ir from IRNIC, point A record to host IP (Pars Pack gives IP)
3. In cPanel, create MySQL DB `unify_db`, user, import `database.sql` migrations via phpMyAdmin or `php artisan migrate`
4. Upload Laravel backend: Zip `/unify-backend` outside `public_html`, unzip, run `composer install --no-dev`, set `.env` DB credentials, APP_KEY, PUSHE_API_KEY, and run `php artisan storage:link` — it creates the symlink `public/storage -> storage/app/public` (the `/storage/...` URL space every file response uses; see V2-12, Round-2 audit)
5. Upload React build: `npm run build` locally, upload `dist/*` to `public_html` (or `public_html/frontend` if you want separate), set `.htaccess` for SPA fallback + API proxy
6. Set cron: cPanel -> Cron Jobs -> Add `* * * * * cd /home/username/unify-backend && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1`
7. SSL: cPanel -> Let's Encrypt -> Issue for domain, auto renew
8. Test: Open domain, login with temp password from envelope

**Two-Host Option (User wanted to use both links):**
- Host Iran (https://parspack.com/host/iran) for frontend static (low ping Iran, half-price traffic) - upload React build to its public_html
- Cloud Host (https://parspack.com/host/cloud-host) for backend API + MySQL + files - point subdomain api.yourdomain.ac.ir to Cloud Host IP, frontend calls api subdomain
- Benefit: Frontend fast low ping, backend more CPU
- For 600 students, single Shop host is simpler, both hosts option optional

**Android APK (Optional):**
- Build Capacitor Android wrapper that loads `https://yourdomain.ac.ir` via `server.url` in capacitor.config.json
- Host APK at `https://yourdomain.ac.ir/app.apk` on same Cloud Host, provide QR on login page

### 22. What We Lose vs V8 VPS

- No WebSocket real-time, polling 15s delay
- No Celery precise second jobs, cron minute precision
- No MinIO S3 infinite, local 10GB limit with manual LRU cron
- No Redis fast cache/idempotency, MySQL table slower but works
- No Docker, no separate services, single host
- Concurrency 2000 -> 200-300 before throttling
- No iOS (already dropped)
- But ALL business features kept for 600 students MVP

END V9 DEFINITIVE
