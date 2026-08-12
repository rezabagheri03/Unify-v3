# 05 - AGENT INSTRUCTIONS - How an Agentic LLM (like you) Must Build Unify V9

This file is the brain. Read this FIRST before any other doc. It tells you in what order to read docs and build, and what rules you MUST NOT break.

## 0. Who You Are
You are an agentic LLM, same as the one that wrote these docs. You will receive all 70+ files in `/Final_Project/` as context. Your job is to fully create the project ready to deploy on **Pars Pack Cloud Host + Host Iran** (no VPS, no self-host, no iOS, Android APK optional direct download) for 600 CS students.

## 1. Non-Negotiable Rules - NEVER Break

1. **Honor System MUST stay** - `academic_status_declared` is self-declared checkbox Normal/Conditional/GPA_A/Final Semester with acknowledge. Do NOT enforce against transcript. Only log + flag abuse >2 times final_semester. Never block based on transcript.

2. **Intranet Must-Have via Polling + Pushe PHP - FIXED per user choice 30s polling** - Shared host cannot run WebSocket. Use polling `GET /api/notifications/unread?since=` every 30s foreground, 120s background via setInterval + Workbox Background Sync + file cache 5s per user (Cache::remember 5s) to reduce MySQL max_connections (600 users polling 15s = 40 req/s -> 30s = 20 req/s). For Android, call Pushe API via PHP curl `https://api.pushe.co/v2/` with `PUSHE_API_KEY` env. No separate Socket.IO server. iOS removed entirely.

3. **IT Handout Physical MUST stay** - No forgot-password email. Initial password is 12 chars `Str::random(12)` physically handed in sealed envelope PDF generated via dompdf + QR, `must_change_password=1`, expires 7 days, forced onboarding first_name/last_name + change password. Bulk import generates ZIP of envelopes.

4. **No iOS** - Forget iOS app totally. At most Android APK direct download hosted at `/app.apk` on same host.

5. **File Storage 50GB Total (FIX C4 User Choice upgrade_50gb)** - Cloud Host Shop 10GB + 40GB extra block storage via Pars Pack ticket = 50GB total, cost ~1.2-1.5M/month, truly evergreen for 4 years. Shop base 10GB alone would fill in 1 semester (600*5 files*3MB=9GB + ticket images 4.8GB + assignments 6GB = ~20GB/semester). So we upgrade to 50GB total, no LRU deletion needed for 2-3 years. Keep LRU cleanup cron daily as fallback to keep under 80% of 50GB (40GB) until 70% (35GB), protected professor files never auto-evicted.

6. **Stack Locked for Shared Host:** React PWA static + Laravel 10 PHP 8.2 + MySQL 8 + local filesystem + Cron (not Celery) + Polling (not WebSocket) + IndexedDB queue (only 5 safe entity types). Do NOT try to use Docker, Redis, Postgres, MinIO, Socket.IO, FastAPI.

## 2. Order to Read Docs

1. Read `00_README.md` + `01_DEFINITIVE_SPEC.md` (master spec)
2. Read `UX_FLOWS/VISUAL_UX_FLOWS.html` + 5 PNG flowcharts (visual understanding)
3. Read `04_UX_FLOWS_FULL.md` (step-by-step user journeys)
4. Read `ROLES/` 7 files (who can do what)
5. Read `FEATURES/` 20 files (business logic)
6. Read `PAGES/` 19 files (UI)
7. Read this file `05_AGENT_INSTRUCTIONS.md` (how to build)
8. Read `06_API_OPENAPI.yaml` (machine-readable API contract - you MUST follow exact request/response shapes)
9. Read `07_DATABASE_MIGRATIONS/` (15 migration files - run in order)
10. Read `08_ENV_EXAMPLE.md` + `.env.example` files
11. Read `09_PROJECT_STRUCTURE.md` (where files go)
12. Read `10_TESTING_STRATEGY.md`, `11_ACCEPTANCE_CRITERIA.md`, `12_SECURITY_CHECKLIST.md`, `13_ERROR_HANDLING.md`

## 3. Order to Build (11 Milestones - DO NOT skip order)

**Milestone 1 - Foundation (2 days) - FIXED C1 C2 H1 H3 H5 etc.:**
- Setup Laravel 10 project in `unify-backend/` outside `public_html`
- Create MySQL DB `unify_db` via cPanel
- Run migrations `07_DATABASE_MIGRATIONS/` in order (13 files now including fixes: departments, users + password_histories, semesters, courses + prereq/coreq, course_specifications with day_of_week fix + is_next_day BOOL for overnight support per user choice support_overnight, student_passed_courses with unique student_id+course_id+entry_year FIX M5 + academic_status_at_enrollment + composite index, enrollments with status archived + academic_status_at_enrollment FIX C2 + academic_status_history table for abuse detection, resources with family_id nullable FIX C1 chicken-egg + last_downloaded_at FIX H2 + is_protected, resource_ratings, sticky, upload_counts, messages + read_status, tickets + replies + daily_counts, other tables assignment etc., system tables device_tokens, audit_logs encrypted, idempotency_keys with expires_at index, notifications with type ENUM FIX M10 + read index, mutes, system_configs, honor_flags with resolved_at resolver_id, broadcast_throttles FIX H3, download_daily_counts FIX H5 20/day limit, resource_download_logs FIX H2 LRU, storage_stats FIX C4 50GB, academic_status_history FIX C2)
- Implement Auth: `User` model with Argon2id `Hash::make`, `must_change_password`, `temporary_password_expires_at`, Sanctum SPA cookie, rate limit 5/15min file cache, AuditLog `login`/`failed_login`, IT envelope PDF dompdf + QR simple-qrcode
- Seed Owner user
- Implement fixes: family_id nullable initially then Observer sets family_id=id, academic_status_history table for final_semester >2 distinct semesters abuse detection, is_next_day flag for overnight labs

**Milestone 2 - Scheduler + Honor (3 days):**
- Implement `Course`, `CourseSpecification` with day_of_week ENUM sat-sun-mon-tue-wed-thu-fri, time_start/end, exam dates Shamsi, semester_id, is_active
- Implement `StudentPassedCourse`, `Enrollment` with status temporary/finalized/archived + version
- Implement Honor System: radio 4 + acknowledge, store academic_status_declared + count + last_declared + is_honor_acknowledged, abuse detection final_semester >2, banner yellow
- Implement Scheduler Phase A: Search specs semester=current is_active=1, add/remove temp `POST /api/v1/enrollment/temp` with idempotency key MySQL table `IdempotencyKeys`, check time overlap day_of_week + time intervals, exam overlap same day 2h buffer, prereq warning modal not block, credit limit per honor status
- Implement Phase B weekly timetable Sat-Wed grid, Phase C exam flip Framer Motion reduced motion fallback
- Implement Golden Scheduler: PHP backtracking with MRV heuristic, timeout 5s, max 1000 combos, scoring freeDays*20 -gap*10 +profBonus*15, top 15, cache table `GoldenScheduleCache`
- Implement Grace Period: Admin switches enrolling->active sets `grace_period_ends_at` now+24h Asia/Tehran, Cron every 5 minutes `enrollments:wipe-grace` hard deletes temp + lazy check fallback in EnrollmentController@final (if grace_period_ends_at <= now and handled=0 then run wipe immediately) FIX C3: many shared hosts limit cron min to 15 min on Starter, but Shop allows 5 min, plus lazy check handles cron failure, notifies affected via Notification table + Pushe PHP curl
- Implement Overnight Support: CourseSpecification has is_next_day BOOL default 0, validation time_end > time_start when is_next_day=0, allow time_end < time_start when is_next_day=1 (e.g., 22:00-02:00), weekly timetable shows as two blocks 22:00-24:00 today + 00:00-02:00 next day FIX H9 per user choice support_overnight

**Milestone 3 - Resource Hub Evergreen (3 days) - FIXED C4 50GB + H2 LRU tracking + H4 .htaccess protection + H5 download limit:**
- Implement `Resource` with course_id+professor_id evergreen, family_id nullable initially then set to id FIX C1 chicken-egg, previous_version_id, is_superseded, scheduled_hard_delete_at now+30d, file_path `/uploads/resources/...` (validate filename max 255 no .php anywhere FIX H10), file_size, file_mime magic finfo PDF/DOCX only 50MB, quota 5/day via MySQL table `ResourceUploadCount`, last_downloaded_at for LRU FIX H2, is_protected for professor badge never auto-evicted
- Upload flow: Student upload -> temp -> pending -> notifies approvers via Notification + Pushe, Approval Professor auto-approved badge professor, Student pending, approve moves temp to permanent, reject with reason, notify
- Versioning family_id preserves ratings, old scheduled hard delete 30d via cron daily `resources:cleanup-old-versions`
- Download: GET /api/v1/resources/{id}/download direct file, increments download_count + last_downloaded_at + resource_download_logs table FIX H2 + download_daily_counts table FIX H5 20/day limit per student (600*20*5MB=60GB/day max if abused, but rate limit prevents fair usage 2TB exceed), caches via Cache API, LRU eviction: Client Cache API 100MB, server side LRU cron daily `files:lru-cleanup` to keep under 80% of 50GB (40GB) until 70% (35GB) FIX C4 50GB upgrade truly evergreen for 4 years, protected professor files never deleted
- Security: Create `/public_html/uploads/.htaccess` with `php_flag engine off` + `Deny from all` for php files + Allow pdf/docx/png/jpg FIX H4 to block shell.pdf.php attack
- Rating: POST rating replaces old, average excludes self is_self_rating, distribution, self rating flagged, Rating tab optional snackbar after 30s not forced popup
- Sticky Note: Private encrypted via Crypt::encryptString, UNIQUE student+family_id, max 1000 chars

**Milestone 4 - Messaging Unified + Polling (2 days):**
- Implement `Message` with sender_id, recipient_id nullable, specification_id nullable, subject, body, sent_at, is_edited, is_deleted placeholder, parent_message_id thread, priority, `MessageReadStatus` table
- Broadcast fan-out: Single row spec_id set recipient_id null, inbox query WHERE recipient_id=self OR (spec_id IN my enrolled spec ids AND is_deleted=0)
- Inbox tabs All/Unread/Classes/Private/System, Search, Virtualized, Read dot blue, Edited badge, Deleted placeholder italic
- Detail thread chat bubbles self right blue other left gray, Reply creates private thread to original sender professor if broadcast, System reply hidden
- Edit/Delete by Professor only: PATCH sets is_edited, DELETE sets is_deleted placeholder, push irreversible documented, polling shows placeholder
- Read Status: POST /api/v1/messages/{id}/read creates MessageReadStatus
- Polling: Every 15s GET /api/notifications/unread?since=lastTimestamp for new_message, message_updated, message_deleted

**Milestone 5 - Ticketing + Escalation Cron (2 days):**
- Implement `Ticket` + `TicketReply` split, student_attachments JSON max 3 images 5MB each finfo image, staff_attachments any except exe 20MB, status open/in_progress/answered/closed, assigned_to, is_escalated, escalation_level
- State machine: Open -> In Progress (Expert assigns to self) -> Answered (Expert replies) -> Closed (Expert/Admin closes), Student reply when answered reverts open
- Student create: Dept education/technical/student_affairs, subject max100, desc max2000, images max3, rate limit 5/day via MySQL
- Staff queue filters dept own dept Expert all Admin, assign to self sets in_progress, reply text + file, close with reason
- Escalation Cron hourly `tickets:escalate` checks no staff reply 48h -> is_escalated=1 escalated_at now level 0->1 Expert->Admin, notify Admin via Notification + Pushe, if Admin no reply 48h -> level2 Owner
- Related ticket button prefilled [مرتبط با #ID]

**Milestone 6 - Curriculum Charts (2 days):**
- Implement `CurriculumChart` id, department_id, entry_year, chart_data JSON MySQL 8 JSON type, status draft/pending_approval/approved, approver_id, approved_at, version
- `StudentPassedCourse` checkbox OR merge once true stays true unless explicit uncheck confirmation modal
- Tree expandable semesters 1-12, courses rows checkbox passed, Course Detail Modal prereq list with passed status, Progress bar credits passed/required
- Upload/Edit flow Expert: Tree editor drag-drop dnd-kit, Import Excel, Save as Draft PATCH, Submit for Approval POST pending_approval notifies Head, Head final approval Approve/Reject with diff view added green removed red

**Milestone 7 - Forms, Calendar, Notice, FAQ, Assignment (3 days):**
- Forms: Title description file PDF/DOCX 20MB signature_guide one-liner, dept + univ, download direct /uploads/forms/{dept}/{uuid}.pdf, Cache API, notification low polling + Pushe on new univ form
- Academic Calendar: Timeline horizontal + Calendar Jalali month dots, detail modal with action button to scheduler, integration warning if registration_close passed but global_state still enrolling (Admin only), notifications 7-day 24h via cron daily `calendar:warn`
- NoticeBoard: Title content priority low/medium/high banner_color expires_at, high priority push via Pushe + polling, low/medium in-app only
- FAQ: Question Answer is_pinned, pinned first, accordion
- Assignment Tracker: Title description spec dropdown own finalized due Shamsi reminder 1/3/24/72h attachment 20MB PDF/DOCX/ZIP, status pending/submitted/graded/late/missed, late detection cron hourly due_date<now and pending->late, missed after 7d, local notification via Capacitor LocalNotifications scheduled at due-reminder, grade via professor, grade push via polling + Pushe

**Milestone 8 - Semester Transition + Archive (1 day):**
- Define new semester: POST /api/v1/admin/semesters, transaction old is_current false, old specs is_active false, old finalized enrollments->archived, old temp hard deleted, resources untouched, notification to all
- Archive dropdown UI student top current+past where archived enrollments exist read-only gray overlay
- Grace period interaction block new temp during grace, block defining new semester while grace active

**Milestone 9 - Notification Polling + Pushe PHP + Mute (2 days):**
- No WebSocket, polling primary GET /api/notifications/unread?since= every 15s foreground 60s background via setInterval + Workbox Background Sync
- Android Pushe via PHP curl Pushe API with PUSHE_API_KEY env, DeviceToken table provider pushe, is_active, for each notification event check DeviceToken where provider=pushe
- iOS removed entirely
- SMS fallback optional Kavenegar API via PHP curl for critical if opted-in
- Intranet detection: Client checks internal /health reachable but external google.com not -> isIntranetMode yellow badge "حالت اینترانت - بروزرسانی هر ۱۵ ثانیه"
- Mute per spec NotificationMute table user_id+spec_id UNIQUE muted BOOL, polling checks mute before sending except critical bypasses mute
- Priority table: critical polling+Pushe+SMS+local, high polling+Pushe, low polling only

**Milestone 10 - Excel Import/Export Transactional (2 days):**
- PhpSpreadsheet, UTF-8 Persian headers row1 English hidden row2, data row3, Client SheetJS, validate header row, 2000 row limit 5MB MIME check, Shamsi valid Morilog\Jalali, Time HH:MM, Telegram URL https://t.me/, Dept exists, Role enum mapping Persian "دانشجو"->student etc, Boolean بله/خیر mapping
- Import flow transactional: BEGIN validate all rows collect errors array row_number column error_message Persian raw_value, if any error ROLLBACK + error report Excel column خطا red highlight, no partial, if all valid COMMIT + AUDIT, for Users generate ZIP envelopes dompdf
- Export: Shamsi YYYY/MM/DD credits int boolean Persian بله/خیر, respects scope Expert own dept only Admin all, Student cannot export

**Milestone 11 - Security, Audit, Theme, Offline Sync, Grace (2 days):**
- AuditLog id user_id action resource_type resource_id timestamp ip user_agent details JSON encrypted via Crypt::encryptString with APP_KEY is_suspicious, Middleware logs DELETE/PATCH sensitive role/is_banned/academic_status/password/file/message/ticket, Viewer Owner only filterable decrypted details diff modal export CSV/Excel requires reason + audit export action
- Security: Argon2id via Hash::make argon2id, Rate limiting Laravel throttle file cache, JWT Sanctum httpOnly SameSite Strict CSRF, File upload magic finfo, ClamAV optional, External links external browser confirmation, Intranet compliance polling + Pushe
- Theme: Uploadable logo Admin max 2MB PNG/SVG sanitized HTMLPurifier, brand_name default Unify editable Admin, MUI ThemeProvider RTL, 5 presets Unify Blue primary #1976D2, Dark mode toggle all roles, Department default theme Head set, Custom CSS variables, Exam flip Framer Motion reduced motion fallback fade
- Offline Sync: IndexedDB via idb-keyval, SyncQueue key unify-syncQueue array id entity_type action payload idempotency_key status pending/syncing/synced/failed/conflict attempts last_error created_at, FileCache via Cache API, NotificationMuteLocal, UserPreferencesLocal, Background sync every 2 min when online + Workbox Background Sync, Idempotency MySQL table IdempotencyKeys key UNIQUE user_id response_code response_body expires_at 24h, Smart merge per entity rating last-write-wins, sticky last-write, curriculum OR merge, assignment last-write + status rules, enrollment NOT QUEUED in V9 requires online
- Grace Period: Already in Milestone 2

## 4. File Structure to Create (V9 Shared Host)

```
/home/username/ (cPanel home)
  /unify-backend/ (Laravel outside public_html)
    /app/Models/ User, Department, Semester, Course, CourseSpecification, StudentPassedCourse, Enrollment, Resource, ResourceRating, ResourceStickyNote, Message, MessageReadStatus, Ticket, TicketReply, AssignmentTracker, CurriculumChart, FAQ, NoticeBoard, Form, AcademicCalendar, DeviceToken, AuditLog, IdempotencyKeys, Notification, NotificationMute, GoldenScheduleCache, ResourceUploadCount
    /app/Http/Controllers/Api/ AuthController, OnboardingController, SpecificationController, EnrollmentController, GoldenScheduleController, ResourceController, RatingController, StickyNoteController, MessageController, TicketController, CurriculumController, FormController, CalendarController, NoticeBoardController, FAQController, AssignmentController, SemesterController, Admin/UserController, Admin/BrandingController, Owner/UserController, NotificationController, DeviceController
    /app/Services/ PusheService (curl Pushe API), KavenegarService, ShamsiService (Morilog\Jalali wrapper), FileCacheService (local LRU 10GB)
    /app/Console/Commands/ EnrollmentsWipeGrace, TicketsEscalate, CalendarWarn, ResourcesCleanupOldVersions, FilesLruCleanup
    /app/Http/Middleware/ AuditLogMiddleware, RoleMiddleware
    /routes/api.php (all endpoints from OpenAPI spec)
    /database/migrations/ 15 files from 07_DATABASE_MIGRATIONS/
    /database/seeders/ OwnerSeeder, DepartmentsSeeder, CoursesSeeder for 600 students
    /storage/app/public/ -> symlink to /home/username/public_html/uploads (created via php artisan storage:link)
  /public_html/ (React PWA static + Laravel public for api subdomain)
    /index.html (React build)
    /assets/ (React JS/CSS)
    /uploads/resources/{course_id}/{professor_id}/{uuid}.pdf
    /uploads/forms/{dept}/{uuid}.pdf
    /uploads/branding/logo.png
    /uploads/assignments/{student}/{uuid}.pdf
    /app.apk (Android direct download optional)
    /.htaccess (SPA fallback + api proxy)
  Frontend source (local dev, not on host):
  /frontend/
    /src/
      /api/ client.ts Axios with Idempotency-Key header UUID + polling hook useNotificationsPolling()
      /db/ idb.ts idb-keyval wrapper for syncQueue, fileCacheMeta
      /stores/ authStore, schedulerStore, resourceStore
      /components/ CourseCard, FileCard, Timeline, SearchBar, ShamsiDatePicker, FlipCard, Banners, etc. from P18
      /screens/ Student Dashboard, Scheduler A/B/C, Resource Hub, Inbox, Ticketing, Curriculum, Forms Calendar, Assignment, Settings, Professor, Expert, Head, Admin, Owner pages from P00-P18
      /utils/ shamsi.ts date-fns-jalali wrapper, validators
```

## 5. Definition of Done for Each Milestone

- All API endpoints from OpenAPI spec implemented and return exact shapes as spec, with Persian error messages as per 13_ERROR_HANDLING.md
- All validations as per FEATURES docs (credit limits, time overlap day_of_week, exam overlap, prereq warning, 50MB file, 5/day quota, 3 images max 5MB, etc.)
- Polling works: After spec time change, enrolled students see banner within 15s (not instant) + Pushe push if Android token exists
- Cron jobs defined in app/Console/Kernel.php and cPanel cron set `* * * * * php artisan schedule:run`
- Offline queue: IndexedDB queue for 5 safe types works offline then syncs when online, shows in /settings/offline-queue
- AuditLog created for every sensitive action with IP + UA + encrypted details
- Rate limiting 5/15min login via throttle middleware
- Argon2id hashing verified via Hash::check
- File upload magic bytes finfo check, not extension
- Excel import transactional rollback + error report Excel with column خطا red
- No business feature lost vs V7, only degraded real-time polling 15s delay and 10GB disk limit

## 6. What NOT to Do

- Do NOT use Docker, Redis, Postgres, MinIO, Socket.IO, FastAPI, SQLite, SQLCipher, Capacitor iOS, APNs - these are V8 VPS stack, not allowed for shared host
- Do NOT trust local role for authz - server validates every request via RoleMiddleware + Policies row-level dept scope
- Do NOT force rating popup - optional snackbar after 30s viewing
- Do NOT allow self to message banned unless Admin (Expert cannot)
- Do NOT allow closed ticket reopen - must create new related ticket with prefilled [مرتبط با #ID]

## 7. Final Checklist Before Saying Done

- Run `php artisan migrate --force` on Cloud Host - all 15 migrations succeed
- Run `php artisan db:seed --class=OwnerSeeder` - owner exists
- Upload React build to public_html - login page shows
- Test IT envelope flow: Owner bulk import 600 Excel -> ZIP 600 PDFs generated -> print one, login with temp, forced onboarding, change password
- Test honor system: Student declares final_semester, adds overlapping specs, finalizes -> allowed with warning banner, count++ logged, abuse flag after 2 distinct semesters
- Test enrollment: 2 students try to add same spec same time overlapping with other spec -> time overlap blocked unless final_semester, credit limit enforced, prereq warning modal
- Test grace period: Admin switches enrolling->active sets grace_period_ends_at now+24h, students with temp see countdown red, after 24h cron hard deletes temp, notification to affected
- Test resource hub: Student upload 6th file today 429 quota, Professor upload auto-approved badge professor, Pending approval queue Expert, Approve moves temp to permanent /uploads, notifies enrolled via polling + Pushe
- Test ticketing: Student creates ticket with 3 images, Expert replies status answered, Student replies reverts open, Close -> cannot reopen, Cron escalation after 48h is_escalated=1
- Test polling: Change spec time/location as Expert -> enrolled students see banner within 15s + Pushe push if Android
- Test offline: Go offline Chrome DevTools, add rating, sticky note, ticket reply -> queued IndexedDB pending -> go online -> background sync every 2min syncs + shows in offline queue page synced
- Test 10GB limit: Upload many files until /uploads/resources size >8GB, cron daily files:lru-cleanup deletes least recently downloaded non-protected until <7GB
- Test Excel import: Upload Excel with one row invalid Shamsi date 1403/13/40 -> rollback no partial + error report Excel column خطا red
- Test audit logs: Owner can view logs filtered, decrypted details diff, export requires reason
- Test deployment: Single Cloud Host Shop 10GB/5vCPU/7GB RAM handles 200 concurrent enrollment peak without 503

If all above pass, you are done.

END AGENT INSTRUCTIONS
