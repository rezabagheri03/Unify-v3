# 10 - Testing Strategy - V9 Shared Host

This doc tells agentic LLM what to test, how, and in what order. Without tests, LLM will say "done" but enrollment will have time overlap bug.

## 1. Unit Tests - Backend Laravel (PHPUnit)

Run via `php artisan test` on dev, and also on Cloud Host via cPanel Terminal.

### Scheduler Constraint Engine (Most Critical - day_of_week fix)

**File:** `tests/Unit/SchedulerTest.php`

- Test time overlap same day: specA Sat 8-10, specB Sat 9-11 -> must detect overlap true
- Test time overlap different day: specA Sat 8-10, specB Sun 8-10 -> false
- Test time overlap edge: specA 8:00-10:00, specB 10:00-12:00 -> false (end == start not overlap)
- Test overnight not supported: time_end < time_start should fail validation, error "زمان پایان قبل از شروع"
- Test exam overlap same day Gregorian with 2h buffer: examA 1403/04/20, examB 1403/04/20 same day -> overlap true, examA 1403/04/20, examB 1403/04/21 -> false
- Test credit limits per honor status: Normal min 12 max 20, Conditional max 14, GPA_A max 24, Final max 24 + ignore time/exam conflicts
  - Normal 11 credits -> fail validation "حداقل 12 واحد برای حالت عادی"
  - Normal 21 credits -> fail "سقف 20 واحد"
  - Final 18 credits with time overlap -> pass with warning "تداخل نادیده گرفته شد (ترم آخر)"
- Test prereq warning: StudentPassedCourse does NOT contain prereq course_id -> should return warning not block, with message "پیش‌نیاز X را پاس نکرده‌اید"
- Test coreq allow: Co-req not in temp nor passed -> allow with warning

### Golden Scheduler Backtracking

**File:** `tests/Unit/GoldenSchedulerTest.php`

- Test backtracking returns top 15, not brute permutation explosion: Input 40 courses * 2 specs each = 80 specs, should return within 5s, max 1000 combos evaluated, timeout 5s server
- Test scoring: freeDays*20 -gap*10 +profBonus*15 -daysWithClasses*5, check that suggestion with 2 free days scores higher than 0 free days
- Test credit limit enforced: creditLimit 20, remaining courses all 3 credits, combos should all <=20
- Test final_semester ignores time conflicts: Same overlapping specs should be included if status final_semester
- Test empty remaining courses (all passed) -> returns message "تمام دروس را پاس کرده‌اید" empty array

### Honor System Abuse Detection

**File:** `tests/Unit/HonorSystemTest.php`

- Test declaration_count increments on each POST /api/v1/users/me/academic-status
- Test final_semester declared >2 distinct semesters -> flag final_semester_abuse_flag true, AuditLog is_suspicious true, notification to Expert created
- Test switching status 3 times within same phase enrolling -> flag frequent_switch

### Resource Hub Validation

**File:** `tests/Unit/ResourceTest.php`

- Test file mime magic bytes: Upload exe renamed pdf `MZ...` -> fail 400 "فرمت فقط PDF و DOCX" via finfo
- Test file size 51MB -> fail 400 "حجم فایل حداکثر 50 مگابایت"
- Test quota 5/day: Create 5 resources same user same day -> 5 success, 6th -> 429 "سقف روزانه 5 فایل"
- Test versioning family_id: Create resource v1, upload new version v2 with previous_version_id v1, check family_id same as v1 family_id, version 2, previous_version_id v1, is_superseded true for old, scheduled_hard_delete_at now+30d
- Test rating average excludes self: Student A uploads, Student A rates 5 (is_self_rating true), Student B rates 3 (is_self_rating false) -> average should be 3 not 4, rating_count 1 not 2

### Ticketing State Machine

**File:** `tests/Unit/TicketTest.php`

- Test state transitions: Open -> In Progress (Expert assigns to self) -> Answered (Expert replies) -> Closed (Expert closes), Student reply when Answered reverts to Open
- Test student cannot reply to closed -> 403 "تیکت بسته شده - تیکت جدید ثبت کنید"
- Test escalation cron: Ticket open, no staff reply 48h -> is_escalated true, escalated_at now, escalation_level 0->1, assigned Admin

### Excel Import Transactional

**File:** `tests/Unit/ExcelImportTest.php`

- Test import with one row invalid Shamsi 1403/13/40 -> rollback no partial, error report Excel with column خطا red highlight row number + error Persian, no DB changes
- Test duplicate ID within file -> error "شناسه تکراری در فایل"
- Test duplicate ID in DB for Users -> error "شناسه {id} قبلا وجود دارد - ردیف {row}"
- Test valid 10 rows -> commit 10 rows inserted, AuditLog major_edit count 10

## 2. Integration Tests - Backend + MySQL + Cron

Run via `php artisan test --group=integration`

### Offline Sync Idempotency MySQL

**File:** `tests/Feature/SyncIdempotencyTest.php`

- Send POST /api/v1/resources/{id}/rating with Idempotency-Key UUID same key twice -> second returns same response code 200 and same body without double create, check IdempotencyKeys table has key, expires_at 24h
- Send same key with different user -> should be treated as different? Actually key should be per user, so same key different user should be allowed? Decision: key unique globally, so second user same key -> return previous response? Better key unique per user, so check user_id + key unique, test both

### Grace Period Cron

**File:** `tests/Feature/GracePeriodTest.php`

- Admin switches enrolling->active sets grace_period_ends_at now+24h, creates enrollments temporary for student, Cron command `enrollments:wipe-grace` run when now >= grace_ends -> hard deletes temp, sets handled=1, notifies affected via Notification table + Pushe mocked

### File LRU Cleanup Cron for 10GB Shop Limit

**File:** `tests/Feature/FileLruCleanupTest.php`

- Simulate /uploads/resources size >8GB (80% of 10GB), run `files:lru-cleanup` command -> deletes least recently downloaded non-protected (is_protected false) until size <7GB, protected professor badge files never deleted

### Polling Notifications

**File:** `tests/Feature/NotificationPollingTest.php`

- Expert changes spec time/location/day for enrolled spec -> creates Notification rows for enrolled students, GET /api/notifications/unread?since=lastTimestamp returns new notification within 15s polling window, PusheService mocked should be called if DeviceToken exists

### Enrollment Conflict via Polling

**File:** `tests/Feature/EnrollmentConflictTest.php`

- Student A finalized 2 specs Sat 8-10 and Sun 8-10, Expert changes specB from Sun 8-10 to Sat 9-11 (now overlaps with specA), polling endpoint should return spec_changed event, Student A's dashboard should show critical banner red "تداخل برنامه به دلیل تغییر {course}" and conflict warning in timetable

## 3. Frontend Unit Tests - React (Vitest + Testing Library)

### Components

**File:** `frontend/src/components/__tests__/CourseCard.test.tsx`

- Renders Course Name bold, Professor, Day+Time, Location, Credits, Exam date
- Header color deterministic hash professor_id hue, contrast WCAG AA check (luminance)
- Footer buttons Download Resources navigates /resources?course_id=&professor_id=, Class Group opens external browser confirmation dialog, Details opens modal
- Conflict state red border + warning icon

**File:** `frontend/src/components/__tests__/FileCard.test.tsx`

- Icon PDF red DOCX blue, Title, Author, Shamsi date, Rating avg + count, Download count, Badge professor/expert/admin, Version, Cache status cloud/check, Pinned
- Download button triggers GET /api/v1/resources/{id}/download + Cache API save
- Pin button toggles IndexedDB FileCacheMeta is_pinned

**File:** `frontend/src/components/__tests__/ShamsiDatePicker.test.tsx`

- Input Shamsi YYYY/MM/DD valid Jalali -> converts to Gregorian via date-fns-jalali for API
- Invalid Shamsi 1403/13/40 -> inline error Persian "تاریخ شمسی نامعتبر"

### Stores

**File:** `frontend/src/stores/__tests__/authStore.test.ts`

- Zustand + idb-keyval persist, login stores user, token, must_change_password, Honor status

### Utils

**File:** `frontend/src/utils/__tests__/shamsi.test.ts`

- toShamsi Gregorian -> Shamsi YYYY/MM/DD, toGregorian Shamsi -> Gregorian, isValidJalaali

**File:** `frontend/src/utils/__tests__/validators.test.ts`

- Credit limits per honor status, time overlap day_of_week, exam overlap, prereq warning

## 4. E2E Tests - Playwright (Critical User Journeys)

Run via `npx playwright test` on local dev with MySQL test DB, and on Cloud Host staging subdomain.

### E2E Flow S01: IT Handout to Dashboard

- Owner bulk import 1 student via Excel -> ZIP envelopes generated -> Student receives envelope PDF -> Login with temp -> Forced onboarding first_name/last_name + change password -> Redirect dashboard -> Dashboard shows empty "برنامه شما خالی است"

### E2E Flow S03: Enrollment Phase A with Honor

- Student declares Final Semester via honor radio + acknowledge checkbox -> Adds overlapping specs (time overlap) -> Should allow with warning "تداخل نادیده گرفته شد (ترم آخر)" -> Credit 18 -> Final Submit -> Success snackbar "ثبت‌نام نهایی شد" -> Weekly timetable shows overlapping blocks side by side 50% width + red border conflict warning

### E2E Flow S06: Resource Upload + Approval + Download

- Student uploads PDF 4MB title course+prof -> pending status, quota 5/day check -> Expert sees pending queue -> Approves -> Student sees approved + notification via polling within 15s -> Another student downloads -> download_count increments, file cached via Cache API -> Rating after 30s optional snackbar -> Rate 4 stars -> Average recalculated excluding self

### E2E Flow S08: Ticketing + Escalation

- Student creates ticket education dept subject description + 2 images 5MB -> Open status -> Expert assigns to self -> In Progress -> Replies text + file any -> Answered -> Student replies -> reverts Open -> Expert closes with reason -> Closed -> Student tries reply -> 403 "تیکت بسته شده" + related ticket button prefilled [مرتبط با #ID]

### E2E Flow S12: Offline Queue

- Go offline Chrome DevTools offline mode -> Student tries add rating, sticky note, ticket reply -> queued IndexedDB pending -> UI shows pending badge in offline queue page -> Go online -> Background sync every 2min syncs + shows synced -> polling fetches new data

## 5. Performance Tests - k6 (For 600 Students)

**File:** `tests/performance/enrollment_peak.js`

- Simulate 200 concurrent students all trying to finalize 18 units at same second during enrollment peak
- Check p95 response <300ms for GET /api/v1/specifications, <500ms for POST /api/v1/enrollment/final
- Check no 503 from shared host CPU throttle (if 503, need to upgrade from Startup 4GB to Shop 7GB RAM or stagger enrollment times)

**File:** `tests/performance/polling.js`

- Simulate 600 users polling every 15s GET /api/notifications/unread -> 600/15 = 40 req/s, check MySQL Notification table index user_id+read handles 40 req/s without slow query (needs index)

## 6. Security Tests - OWASP ZAP

- Run ZAP scan against https://api.unify-cs.ac.ir - check for XSS, SQL injection, file upload bypass (exe renamed pdf should be blocked via finfo)
- Test rate limiting: 6 login attempts per 15min per IP -> 6th should 429
- Test JWT tampering: Modify Sanctum token payload role student -> professor, should 403
- Test local role escalation: Edit IndexedDB authStore role to admin, try POST /api/v1/admin/semesters -> should 403 (server never trusts local)

## 7. Intranet Simulation Test (Manual)

- Docker network with no outside internet, only internal 10.x, deploy backend + frontend
- Test polling: Client on same intranet WiFi should still get notifications via polling to internal IP 10.10.0.5 (or same domain via internal DNS override)
- Test Pushe: Android device on intranet WiFi should receive push via Pushe API (Pushe has Iranian servers, reachable via intranet)
- Test file download: Direct file /uploads/resources/... should be reachable via intranet IP

## 8. Test Execution Order for Agent

1. Unit tests scheduler constraint engine (most critical day_of_week fix) -> run `php artisan test --filter=SchedulerTest`
2. Unit tests honor abuse, resource validation, ticketing state machine, excel import transactional
3. Integration tests idempotency MySQL, grace period cron, file LRU cleanup, polling notifications
4. Frontend unit tests components, stores, utils
5. E2E critical flows S01, S03, S06, S08, S12
6. Performance k6 enrollment peak + polling
7. Security ZAP scan

If any unit test fails, fix before proceeding to next milestone (see 05_AGENT_INSTRUCTIONS)

END TESTING STRATEGY
