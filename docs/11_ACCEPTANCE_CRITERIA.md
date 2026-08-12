# 11 - Acceptance Criteria - Definition of Done for Each Feature - V9 Shared Host

This doc defines when a feature is DONE for an agentic LLM. Without this, LLM will say "done" but enrollment still allows time overlap bug.

## Global DoD (Applies to All Features)

- All API endpoints from `06_API_OPENAPI.yaml` implemented and return exact shapes as spec, with Persian error messages as per `13_ERROR_HANDLING.md`
- All validations from `FEATURES/` docs implemented (credit limits, time overlap day_of_week, exam overlap, prereq warning, file mime finfo max 50MB, quota 5/day, images max 3x5MB, etc.)
- Polling works: After spec time change, enrolled students see banner within 15s (not instant) + Pushe push if Android token exists
- Cron jobs defined in `app/Console/Kernel.php` and cPanel cron set `* * * * * php artisan schedule:run`, tested via `php artisan schedule:run` manually
- Offline queue: IndexedDB queue for 5 safe types works offline then syncs when online, shows in `/settings/offline-queue`
- AuditLog created for every sensitive action with IP + UA + encrypted details via `Crypt::encryptString`
- Rate limiting 5/15min login via throttle middleware file cache, returns 429 with Retry-After
- Argon2id hashing verified via `Hash::check`, PasswordHistory last 3 checked
- File upload magic bytes finfo check, not extension, exe blocked
- Excel import transactional rollback + error report Excel with column خطا red highlight, no partial
- No business feature lost vs V7, only degraded real-time polling 15s delay and 10GB disk limit
- Frontend: Loading skeleton, Empty illustration + CTA, Error red banner + retry, Offline red banner + disabled buttons tooltip "برای این کار نیاز به اینترنت است"

## Feature-Specific Acceptance Criteria

### F01 Authentication + IT Handout

- Owner bulk import Excel 600 rows -> ZIP 600 envelope PDFs generated via dompdf + QR, each PDF contains username temp large monospace, QR, instructions Persian, printed date, operator name, warning 7 days
- Student receives envelope, login with temp, forced onboarding first_name/last_name required + change password complexity min 8 upper lower number special not same temp not in last 3, must_change_password becomes 0, temporary_password_expires_at null, old tokens revoked
- Temp expired 7 days -> 403 "رمز موقت منقضی شده - به IT مراجعه کنید"
- Login 5 attempts per 15min per IP -> 6th 429 "تعداد تلاش‌ها زیاد - 15 دقیقه صبر کنید"
- Forgot password flow: In-person IT visit + Owner search ID + reset generates new temp + invalidates old + envelope PDF + AuditLog password_reset is_suspicious if >2 per month
- Offline login requires online, shows banner "برای ورود نیاز به اینترنت است"

### F02 Honor System

- Radio 4 options Normal 12-20, Conditional max14, GPA_A max24, Final max24 + ignore time/exam, acknowledge checkbox required, POST academic-status stores count++ last_declared + AuditLog honor_status_change
- Credit limit enforced: Normal 11 credits fails "حداقل 12 واحد", 21 fails "سقف 20 واحد", Final 18 with time overlap allows with warning "تداخل نادیده گرفته شد (ترم آخر)" but max 24 enforced even honor
- Abuse detection: final_semester declared >2 distinct semesters -> flag final_semester_abuse_flag true, AuditLog is_suspicious true, notification to Expert dept via polling + Pushe
- Banner yellow "خوداظهاری: ترم آخر - مسئولیت با شماست" always visible when declared

### F03 Scheduler Phases + Grace

- Phase A: Search Name/Code debounce 300ms, filters dept credits day time, list available specs semester=current is_active=1, Add button checks time overlap day_of_week + time intervals, exam overlap same day 2h buffer, prereq warning modal not block, credit limit per honor, if passes adds to temp via POST temp with idempotency key MySQL IdempotencyKeys, time overlap and status != final_semester error snackbar "تداخل زمانی با {course}" block add, final_semester warning allow
- Temporary list sidebar/bottom sheet total credits conflict warnings delete clear all confirmation
- Golden Scheduler button: preferences free days max gap prefer professors morning, Generate GET /api/v1/golden-schedule 5s timeout max 1000 combos scoring freeDays*20 -gap*10 +profBonus*15, top 15 cards score explanation Apply bulk adds
- Final Submit bottom sticky disabled if credit violation honor not declared empty, confirmation modal, POST /api/v1/enrollment/final idempotency key MySQL, success snackbar navigates to active view
- Grace Period: Admin switches enrolling->active sets grace_period_ends_at now+24h Asia/Tehran, cron every minute checks now>=grace_ends and handled=0 -> hard deletes temporary enrollments, handled=1, notifies affected via polling + Pushe "لیست موقت حذف شد", client countdown banner red <2h, Add new temp disabled during grace only Final Submit enabled, Final after grace 403, defining new semester while grace active blocked 400 "مهلت 24 ساعته فعال است"
- Phase B: Weekly timetable Sat-Wed grid 8-18 half-hour slots, specs placed day/time height proportional, click Details modal, read-only, button "مشاهده برنامه امتحانات" navigates to exam, overlap handling side by side 50% width + red border conflict warning if final_semester
- Phase C: Exam flip Framer Motion Front weekly Back linear exam list sorted final Gregorian asc, Front weekly Back linear final blue badge midterm orange, reduced motion fallback fade opacity, Back list rows Course Prof Code Final Date Shamsi Day Time Location Midterm orange badge countdown, Many exams scrollable max height 80vh, Flip during loading disable

### F05 Resource Hub Evergreen

- Evergreen linked to (course_id, professor_id) not semester, optional specification_id context, when semester soft hides old specs is_active 0 resources remain accessible via filtered course+prof
- Types PDF/DOCX only magic bytes finfo not extension, Max 50MB per file, ClamAV optional, Storage local /uploads/resources/{course_id}/{professor_id}/{uuid}.{ext} on Cloud Host Shop 10GB, not S3, client Cache API 100MB LRU via Workbox eviction, professor protected never evicted pin, user can pin, Server no per-user limit but client 100MB, student quota 5/day via MySQL table ResourceUploadCount
- File Card icon PDF red DOCX blue title author Shamsi date avg rating excluding self download count badge professor purple expert blue admin green version cache status cloud/check pinned icon description truncated 100 expandable click detail
- Upload: Student select file title required desc optional course dropdown + professor dropdown evergreen checkbox "ارسال نوتیفیکیشن به همکلاسی‌ها" default false, client validation MIME+size, file stored temp, metadata queued IndexedDB SyncQueue idempotency pending, when online POST /api/v1/resources/upload multipart file stream + metadata, server saves to temp bucket storage/app/temp, creates Resource row status pending version 1 file_path temp file_size mime uploader_id course_id prof_id spec_id nullable average 0, notifies approvers professors course + experts dept via polling + Pushe "جزوه جدید در انتظار تایید"
- Approval Professor/Expert/Admin sees pending queue previews file direct file temp path, clicks Approve moves file from temp to permanent /uploads/resources/.../uuid.pdf status approved badge_type, scheduled_hard_delete_at null, notifies uploader "تایید شد" + if checkbox true notifies enrolled students course+professor "جزوه جدید برای درس X"
- Professor upload immediate status approved badge professor file directly permanent optional notification checkbox default true broadcast to enrolled
- Versioning family_id previous_version_id is_superseded scheduled_hard_delete_at now+30d badge "نسخه قدیمی", new is_superseded=0 current, notification optional enrolled, ratings preserved via family_id
- Download direct file /uploads/resources/... not signed S3 via Nginx direct, increments download_count, caches via Cache API saves to cache dir updates LRU last_accessed success snackbar "دانلود شد - کش شد", Offline if cached opens cached file via Cache API no server call banner "حالت آفلاین - فایل کش شده", If not cached offline cloud icon + "برای دانلود نیاز به اینترنت است"
- Filtering search sort newest/oldest/rated/downloaded paginated 20
- Smart Notification V9 polling + Pushe: On new approved resource if uploader checked notify, Laravel job fans out polling notification + Pushe API curl to enrolled students where enrolled semester=current and course_id+professor_id matches spec course+prof, except muted NotificationMute

### F06 Rating Sticky Versioning

- Rating After download +30s viewing timer client shows non-blocking snackbar bottom "مایلید به این جزوه امتیاز دهید؟" star 1-5 buttons "بعدا"/"ثبت امتیاز" Not forced popup, User can rate anytime detail page rating section, POST /api/v1/resources/{id}/rating {rating 1-5} idempotency key MySQL, Server check approved create/update ResourceRating UNIQUE student+family_id new replaces old is_self_rating = uploader_id == student_id, Recalculate average SELECT AVG rating WHERE family_id=family AND is_self_rating=0 exclude self, if no non-self average 0 "بدون امتیاز" rating_count count non-self Store in Resource rows family denormalized via observer update all versions in family same average, UI detail shows average star + count + distribution chart bars 5-1 My Rating section shows current if rated edit else input 1-5 submit shows "شما آپلودکننده هستید" if self, Professor view feedback distribution but not who rated privacy
- Sticky Notes Private Detail tab "یادداشت شخصی" textarea + save + delete max1000 chars GET /api/v1/resources/{id}/sticky-note returns own note POST create/update UNIQUE student+family_id stored encrypted at rest Crypt::encryptString only creator can view via API check student_id==creator local IndexedDB no encryption but device locked server encrypted UI private badge "فقط شما می‌بینید"
- Versioning Family Concept family_id=id first version every version row has family_id + version + previous_version_id, ratings and sticky linked to family_id not version id preserved, Upload New Version only professor matching course+prof or Admin, file picker + changelog optional POST /api/v1/resources/{id}/new-version file + changelog, Server validate creates new row family_id old.family_id version old.version+1 previous_version_id old.id file new permanent path status approved if professor old is_superseded=1 scheduled_hard_delete_at now+30d badge "نسخه قدیمی" new is_superseded=0 current notification optional enrolled ratings preserved family_id, Viewing Versions tab list family sorted version desc version number upload date Shamsi uploader changelog download button if file exists badge old/new scheduled hard delete date for old versions, Hard Delete Old Version Job Cron daily checks scheduled_hard_delete_at < now and is_superseded=1 deletes file content /uploads sets file_path null is_deleted_content=1 keeps row for audit

### F07 Messaging Unified

- Unified inbox tabs All/Unread/Classes/Private/System with counts badge, List Virtualized messages sorted sent_at desc infinite 20 per page Message Row Avatar sender name bold if unread Subject bold if unread Body preview 80 chars gray Shamsi date small Read dot blue if unread Edited badge small Deleted placeholder italic Priority badge high red, Swipe left mark read/unread, Pull to refresh, Empty No messages illustration, Polling every 15s GET /api/notifications/unread + GET /api/v1/messages?tab= for new
- Detail Header Subject Sender name date Shamsi priority back button Body full is_edited badge edited_at tooltip is_deleted placeholder Thread chain parent->children sorted asc chat bubbles self right blue other left gray body sent_at edited If broadcast banner "ارسال به کل کلاس {course}" Reply section bottom textarea + Send button if broadcast reply creates private thread to original sender professor If system reply hidden If deleted reply hidden placeholder, Data GET /api/v1/messages/{id} includes thread children array sorted asc POST /api/v1/messages/{id}/read on open marks read creates MessageReadStatus, Polling thread updates real-time 15s
- Reply Flow Textarea required max2000 Button Send POST /api/v1/messages/send recipient_id OR specification_id for broadcast? Actually reply private recipient_id original sender subject Re: original body parent_message_id=id idempotency key MySQL, On success new bubble appears list inbox updates polling + Pushe PHP curl to recipient, Edit/Delete Professor only student sees result via polling message_updated/deleted

### F08 Ticketing

- Ticket id UUID student_id dept subject max100 description max2000 status open/in_progress/answered/closed assigned_to is_escalated escalation_level version, TicketReply id ticket_id sender_id body attachments sent_at is_staff, student_attachments JSON max 3 images 5MB each finfo image, staff_attachments JSON any except exe 20MB, status badge colors open gray in_progress blue answered green closed black dept badge, last reply Shamsi assigned escalated red badge, Detail Header ID short Subject Dept badge Status badge Created Shamsi Assigned Escalated badge Close reason if closed Description + student attachments images preview lightbox Timeline TicketReply sorted asc bubbles left avatar student/staff is_staff badge body attachments student images preview staff file download sent Shamsi, Reply Section if closed banner "این تیکت بسته شده" + button "ثبت تیکت مرتبط" navigates new?related_id prefilled [مرتبط با #ID] old subject If open/answered/in_progress textarea + image picker max3 total per ticket Send POST /api/v1/tickets/{id}/reply body attachments, Actions No edit/delete for student replies only staff can close
- Create Form Department dropdown required education/technical/student_affairs with icons Subject text required max100 Description textarea required max2000 Attachments image picker preview thumbnails remove image button validation, Optional related_id query param If ?related_id present prefill subject [مرتبط با #{id}] and description link old ticket, Submit button "ثبت تیکت" On success snackbar "تیکت ثبت شد" navigate detail, API POST /api/v1/tickets multipart department subject description attachments images, Validation Department required Subject max100 Description max2000 Images max3 each 5MB images only mime image/jpeg/png finfo Rate limit 5 per day student via MySQL table 429 "حداکثر 5 تیکت در روز"
- Offline List cached Workbox detail cached create queued IndexedDB with local image staging path reply queued, Edge Reply to closed 403 "تیکت بسته شده - تیکت جدید ثبت کنید" Image 6MB error "حجم هر تصویر حداکثر 5 مگابایت" 4th image error "حداکثر 3 تصویر" Escalated badge red tooltip "این تیکت به ادمین اسکلیشن شده", Staff file attachment download requires online if not cached Cache API, Cron escalation hourly Laravel command tickets:escalate checks 48h no staff reply is_escalated=1
- Notifications Polling + Pushe to student on staff reply, to staff on student reply

### F09-F20 Similar Detailed Criteria - See FEATURES docs for each, must match validations, offline, polling, cron, file storage local 10GB, etc.

### Final Acceptance for Whole Project (600 Students on Cloud Host Shop)

- Single Cloud Host Shop 10GB/5vCPU/7GB RAM handles 200 concurrent enrollment peak without 503 (test via k6 enrollment_peak.js p95 <500ms)
- Polling 600 users * 1 req/15s = 40 req/s handled without slow query (MySQL Notification table index user_id+read)
- 10GB disk: After uploading 600*5 files*3MB avg = 9GB, LRU cleanup cron daily deletes least recently downloaded non-protected until <7GB, no disk full error
- Intranet: Client on same intranet WiFi 10.x can reach server via internal IP 10.10.0.5 or same domain via internal DNS override, polling works even when international gateway cut, Pushe Android push works via Iranian servers
- Android APK direct download at /app.apk works, QR on login page
- No iOS app (per user request)

If all above pass, project DONE.

END ACCEPTANCE CRITERIA
