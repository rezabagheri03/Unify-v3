# 16 - Privacy Policy & User Manual - V9 Shared Host - 600 Students

## Privacy Policy - Persian (Required for 600 Students)

**Must be shown on login page footer link "حریم خصوصی" and in settings.**

### What Data We Collect (per Data Model V9)

- **User:** Student Number / Personnel ID (primary), first_name, last_name, role, department_id, academic_status_declared (honor), is_banned + reason, supplementary_details optional free text, mobile nullable not visible to staff, email nullable not visible to staff, must_change_password, last_login_at, last_name_edit_at
- **Enrollments:** student_id, specification_id, semester_id, status temporary/finalized/archived, academic_status_at_enrollment (for abuse detection), enrolled_at, finalized_at
- **StudentPassedCourses:** student_id, course_id, passed, grade, entry_year
- **Resources:** course_id, professor_id, uploader_id, title, description, file_path, file_size, file_mime, average_rating, rating_count, download_count, last_downloaded_at
- **Ratings:** student_id, resource_family_id, rating 1-5, is_self_rating
- **Sticky Notes:** student_id, resource_family_id, note private encrypted
- **Messages:** sender_id, recipient_id or specification_id, subject, body, sent_at, is_edited, is_deleted placeholder, parent_message_id thread, priority
- **MessageReadStatus:** message_id, user_id, read_at
- **Tickets:** student_id, department, subject, description, status, assigned_to, student_attachments images max 3x5MB, staff_attachments any except exe 20MB, closed_at, escalated_at, is_escalated
- **Assignment Trackers:** student_id, specification_id, title, description, due_date, reminder, status, attachment_path, grade, graded_by
- **DeviceTokens:** user_id, token (Pushe), provider pushe/web_push, platform web/android, is_active
- **AuditLogs:** user_id, action, resource_type, resource_id, timestamp, ip_address, user_agent, details encrypted, is_suspicious
- **Notifications:** user_id, type, title, body, data JSON, priority, read, created_at
- **IdempotencyKeys, Download Counts, etc.**

### Who Can See What (Per RBAC Matrix)

- **Student:** Can view own enrollments, own passed courses, resources filtered course+prof evergreen, own tickets, own assignments, own sticky notes private. Cannot view other students mobile/email, cannot view other students schedule, cannot view audit logs.
- **Professor:** Can view own specs current + archive read-only, student list per spec (student ID, name, academic_status_declared with honor flag, supplementary_details free text if student put contact, but NOT mobile/email), resource ratings average excluding self (distribution but not who rated), download count, not who rated. Can send broadcast to whole class rate limit 1/10min, private to specific student.
- **Expert:** Can view own dept courses/specs, student list any spec own dept, pending resources own dept, tickets own dept. Cannot view other depts. Can message banned? No, only Admin can message banned per fixed spec (Expert cannot). Generic error "کاربر یافت نشد یا دسترسی ندارید" for not found or not in dept or banned to prevent enumeration.
- **Head:** All Expert + final chart approval + professor oversight dashboard (professor ID, name, spec count current, resource count current, last upload date, status green>=1 resource yellow 0 but has specs red no specs).
- **Admin:** University-wide user management search Ban/Unban with reason/expiry, view search all users but still NOT mobile/email (privacy: mobile/email not visible to staff even Admin, only supplementary_details free text if student wants to share contact). Can view escalated tickets (48h+), can hard delete former professors notes with audit. Cannot view full audit logs (Owner only), analytics limited no PII.
- **Owner:** Full read-only entire system + user/role management manual add + bulk Excel import + password reset envelope generation + audit logs viewer with decrypted details diff + analytics full with optional PII (requires reason + audit export action). Can view all.

### Mobile/Email Privacy - Critical Per Spec

- Mobile and email fields in User table nullable, **not visible to staff (professor, expert, head, admin) even**. Only Owner can see? Actually even Owner should not see? Per spec "not visible to staff unless student explicitly includes it in supplementary_details free text". So mobile/email columns are private, only student self and Owner for SMS fallback if opted-in? For SMS fallback critical alerts, if student opted-in "دریافت SMS برای اطلاعیه‌های حیاتی" and provided mobile, then system can use mobile for Kavenegar SMS for critical events only, not visible to staff. Document this.
- Supplementary_details free text optional max 500 chars: If student wants to share contact, they can write "شماره من 0912..." in this free text, then staff can see supplementary_details. This is explicit consent.

### Student Rights Per Iranian Data Protection (Inspired by GDPR but Iranian)

- **Right to Access:** Student can request export of own data via Owner dashboard (Owner can export user data as JSON with all enrollments, passed courses, resources uploaded, tickets, assignments, etc.)
- **Right to Rectification:** Student can edit first_name/last_name once per semester (enforced via last_name_edit_at) + supplementary_details any time + mobile/email any time.
- **Right to Erasure (Right to be Forgotten):** Student can request account deletion via ticket to Admin. Admin can ban user (soft) not hard delete, because enrollments and resources need to be kept for academic records? For V9 shared host, hard delete only for former professors notes by Admin with audit. For student, we implement ban not hard delete. If student insists on erasure, Owner can hard delete user + anonymize enrollments (student_id -> anonymous) + keep AuditLog. Document.
- **Data Retention:** AuditLogs 2 years online then archive to cold storage table AuditLogArchive or S3 JSON dump, still viewable via archive toggle. Resources old versions file content hard deleted after 30 days via cron, but metadata kept. Enrollments archived kept forever for transcript-like reference.

### Security Measures

- Password hashing Argon2id via Hash::make, pepper env, PasswordHistory last 3, must_change_password forced, temporary_password_expires_at 7 days, rate limiting 5/15min file cache.
- AuditLog details encrypted via Crypt::encryptString with APP_KEY, even DB admin cannot read without key.
- File upload magic bytes finfo not extension, block exe, path traversal UUID only, uploads/.htaccess php_flag engine off.
- No foreign dependency for critical: Pushe and Kavenegar have Iranian servers, work on SHOMA, not Firebase.

### Cookies & Tracking

- Sanctum SPA cookie httpOnly secure SameSite Strict, CSRF token X-XSRF-TOKEN double submit, no third-party tracking, no Google Analytics. Analytics dashboard uses own data (active users from last_login_at, download metrics from download_count, engagement from enrollments), no external.

### Contact

- For privacy requests, student creates ticket department Education with subject "درخواست حریم خصوصی - دسترسی/اصلاح/حذف داده‌ها"

## User Manual - Student (Short Version for 600 CS Students)

**Login:** Receive sealed envelope from IT with Student Number + temp password 12 chars, go to https://unify-cs.ac.ir, login, forced onboarding first name last name + change password complexity min 8 upper lower number special not same temp not in last 3.

**Dashboard:** Scrollable course cards current semester, custom colored header hash professor, Day+Time Location Credits Exam date, Notice banner high priority, Footer Download Resources (navigates Resource Hub filtered course+prof evergreen) Class Group Telegram external browser confirmation Details modal Info Exams Notices FAQ, Archive dropdown top to view past semesters read-only gray overlay.

**Scheduler Phase A Enrollment:** Top Honor System radio Normal 12-20 Conditional max14 GPA_A max24 Final max24 + ignore time/exam conflicts + acknowledge checkbox required, Button "ثبت وضعیت", Credit Summary total min/max progress bar, Search bar Name/Code debounce, Filters dept credits day time, Available specs list Add button checks time overlap day_of_week, exam overlap same day 2h buffer, prereq warning modal not block, credit limit per honor, if passes adds to temp list sidebar/bottom sheet total credits delete clear all, Golden Scheduler button preferences free days max gap prefer professors morning Generate top 15 scored, Final Submit bottom sticky disabled if credit violation honor not declared empty, confirmation modal, POST final idempotency key MySQL.

**Resource Hub:** Search filter course professor badge sort newest/oldest/rated/downloaded, FileCard icon PDF red DOCX blue title author Shamsi date average rating excluding self download count badge version cache status cloud/check pin, Detail tabs Preview/Info Rating Sticky Versions, Download direct file /uploads/resources/... increments download_count caches via Cache API, Rating optional snackbar after 30s star 1-5 replaces old average excludes self, Sticky private textarea max1000 private badge, Versions tab list family versions download button old scheduled hard delete 30d, Upload Page course dropdown professor dropdown title required file PDF/DOCX max50MB quota 5/day 429 if 6th, My Uploads tabs pending approved rejected.

**Inbox:** Tabs All/Unread/Classes/Private/System counts badge, List virtualized, Message Row avatar sender name bold unread subject bold unread body preview date read dot blue edited badge deleted placeholder italic priority, Detail thread chat bubbles self right blue other left gray, Reply textarea Send, Edit/Delete by professor only placeholder push irreversible.

**Ticketing:** Create dept subject max100 desc max2000 images max3x5MB preview, List tabs Open Answered Closed All status badge dept badge last reply, Detail timeline TicketReply bubbles student/staff is_staff badge body attachments student images preview staff file download sent Shamsi, Reply textarea image picker max3 total per ticket, Closed banner "این تیکت بسته شده" + related ticket button prefilled [مرتبط با #ID].

**Curriculum:** Filters dept auto own entry year dropdown approved only, Progress Bar credits passed/required, Tree expandable semesters courses rows checkbox passed Course Code Name Credits Required badge Prereq icon Click row modal prereq list with passed status, Checkbox immediate UI + IndexedDB pending + POST passed, OR merge once true stays true unless explicit uncheck confirmation.

**Assignment Tracker:** Kanban/List toggle 5 columns Pending Submitted Graded Late Missed count cards draggable drag Pending->Submitted warning if no file, List table Title Course Due Shamsi countdown Status badge Grade Reminder icon, Detail header Title Status Course Prof Due Shamsi countdown Reminder Description Attachment Grade section if graded, Actions Edit Delete Submit Unsubmit, Timeline, Create form spec dropdown own finalized due Shamsi reminder 1/3/24/72h attachment 20MB, Submit saves schedules local notification at due-reminder via Capacitor.

**Settings:** Theme 5 presets + dark mode Dept default but override, Notifications Global Push toggle SMS fallback toggle mobile field link profile, per-spec mute list enrolled specs toggle, Profile Student Number read-only First/Last editable once per semester, supplementary_details optional, mobile/email nullable not visible staff, Password change old+new complexity, Offline Queue list IndexedDB pending/synced/failed/conflict retry/delete/resolve, Intranet Status isOnline isIntranetMode isOffline badges Details Internal reachable External reachable Polling connected Push provider Pushe Last health check Recheck.

## User Manual - Professor (Short)

Dashboard own specs current + archive read-only, Specs List enrolled count resource count pending approval count avg rating Buttons Students Resources Messages Notice FAQ, Upload Center Course dropdown own dept auto prof=self Title desc File PDF/DOCX max50MB Notification checkbox default true broadcast to enrolled, Resources List My Resources approved own FileCards version rating downloads actions Edit Desc Upload New Version Request Delete Pending Queue List pending own course+prof uploader student title preview Approve/Reject, Students List Table ID searchable Name Academic Status honor flag Supplementary Details free text if contact Enrollment Status finalized Enrolled At Banned badge Actions Send Private Message Export Excel, Messages Broadcast History list broadcast messages spec subject preview sent edited deleted edit/delete buttons if own Private Chats list threads student name last preview unread count, Compose New Broadcast select spec own subject body rate limit 1/10min New Private search student ID/name own enrolled, NoticeBoard CRUD per spec Title Content Priority Banner Color Expires At, FAQ CRUD Question Answer Is Pinned.

## User Manual - Expert/Head/Admin/Owner - See ROLES docs detailed.

END PRIVACY POLICY + USER MANUAL
