# ROLE: STUDENT - V9 Shared Host (Laravel + MySQL + Cloud Host)

## Identity
- ID = Student Number (e.g., 400123456)
- Role = student, department_id nullable
- Auth: Receives envelope from IT (physical), temp password 12 chars, must_change_password=1, expires 7 days, forced onboarding first_name/last_name + change password. Same flow as V7 but Laravel: password hashed Argon2id via Hash::make, must_change_password, temporary_password_expires_at.

## Permissions (Business same as V7/V8)
- CAN: View own schedule temp/final/archived, browse specs current+archive, add/remove temp Phase A only, finalize online only, upload resource 5/day pending, rate (excluding self avg), sticky private, reply messages thread, submit tickets max 3 images 5MB, mute/unmute per spec, view Admin Hub (Curriculum, Forms, Calendar, Assignment, Notice, FAQ), theme/dark mode, edit supplementary_details, edit first/last name once per semester
- CANNOT: CRUD courses/specs, approve, broadcast, ban, set semester, view other students schedule, see staff mobile/email, audit logs, hard delete

## Credit Limits Honor System Kept
- Radio 4: Normal 12-20, Conditional max14, GPA_A max24, Final Semester max24 + ignore time/exam conflicts
- Client validates cached + StudentPassedCourse, shows warnings time overlap (day_of_week + time), exam overlap (same day Gregorian 2h buffer), prereq warn, coreq allow
- On final: POST /api/v1/users/me/academic-status {status, acknowledged} -> stored academic_status_declared + count++ + last_declared + AuditLog honor_status_change
- Abuse: final_semester >2 distinct semesters -> flag final_semester_abuse_flag + notify Expert dept + banner yellow "خوداظهاری ترم آخر"

## Golden Schedule (PHP version)
- Button "پیشنهاد برنامه طلایی" -> GET /api/v1/golden-schedule?preferFreeDays=&maxGap=&preferProfessors= -> Laravel backtracking with MRV heuristic, timeout 5s, max 1000 combos, scoring freeDays*20 -gap*10 +profBonus*15, return top 15, cache table GoldenScheduleCache
- UI cards score explanation, Apply bulk adds to temp

## Resource Hub Student (Shared Host - Local Filesystem)
- View resources filtered course+professor evergreen, search, sort newest/oldest/rated/downloaded
- FileCard: icon PDF/DOCX, title, author, Shamsi date, avg rating excluding self, download count, badge professor/expert/admin, version, cache status (Cache API, not LRU custom protected), pin
- Download: GET /api/v1/resources/{id}/download -> direct file `/uploads/resources/{course}/{prof}/{uuid}.pdf` not signed S3, increments download_count, caches via Cache API
- After 30s viewing optional snackbar rating, POST /api/v1/resources/{id}/rating {rating} replaces old, average excludes self (is_self_rating), rating_count
- Sticky note private: POST /api/v1/resources/{id}/sticky-note {note max1000} encrypted via Crypt::encryptString
- Upload: Max 5/day via MySQL count table, PDF/DOCX magic bytes finfo, 50MB, title/desc required, course+prof evergreen, creates pending -> notifies approvers via polling + Pushe PHP curl if Android, appears after approval
- My uploads tab pending/approved/rejected

## Messaging
- Unified Inbox tabs All/Unread/Classes/Private/System
- Row: Sender avatar, Subject bold unread, Body preview, Shamsi date, read dot blue, edited badge, deleted placeholder "حذف شد", priority
- Detail thread: Chat bubbles self right blue, other left gray, parent_message_id chain, reply creates private thread to sender professor if broadcast
- Edit/Delete by professor only: is_edited badge, is_deleted placeholder, push irreversible (polling will show placeholder)
- Read status: MessageReadStatus table, POST /api/v1/messages/{id}/read on open
- Polling: setInterval 15s GET /api/notifications/unread for new messages

## Ticketing
- Create: Dept education/technical/student_affairs, subject max100, desc max2000, attachments images only max3x5MB preview
- List tabs Open/Answered/Closed/All, status badge, dept badge, last reply, escalated red
- Detail timeline TicketReply, student images preview, staff file download, reply textarea + image picker max3 total, send POST /api/v1/tickets/{id}/reply
- Rules: staff reply -> answered, student reply -> open, Expert/Admin close, closed cannot reopen but related ticket button prefilled subject [مرتبط با #ID]
- Auto-escalation via cron hourly: no staff reply 48h -> is_escalated=1, escalated_at now, assigned Admin, notify Admin via polling + Pushe

## Curriculum Charts
- Filters dept auto own, entry year dropdown approved only, progress bar credits passed/required
- Tree expandable semester nodes, courses rows checkbox passed, Course Code Name Credits Required badge Prereq icon, click course -> modal prereq list with passed status
- Checkbox: Immediate UI + IndexedDB StudentPassedCourseLocal pending + POST /api/v1/curriculum/passed {course_id, passed, entry_year}, OR merge: once true stays true unless explicit uncheck confirmation
- Offline cached tree + checkboxes

## Academic Calendar, Forms, NoticeBoard, FAQ, Assignment
- Calendar: Timeline horizontal + Calendar Jalali month dots, detail modal with action button to scheduler, countdown, filters univ/dept/event_type, integration warning if registration_close passed but phase enrolling (Admin only)
- Forms: Tabs dept + univ, Title desc signature guide one-liner download button, file download manager via direct file `/uploads/forms/{dept}/{uuid}.pdf`
- NoticeBoard: Banners on course card + dedicated per spec page, priority low/medium/high, expires countdown
- FAQ: Per spec accordion pinned first
- Assignment: Kanban/List toggle Pending/Submitted/Graded/Late/Missed, drag-drop Pending->Submitted requires attachment warning, detail title course due Shamsi reminder status attachment grade feedback, create form spec dropdown own finalized, due Shamsi picker, reminder 1/3/24/72h, attachment 20MB PDF/DOCX/ZIP, submit sets submitted, late detection cron hourly, missed after 7d, local notification via Capacitor LocalNotifications scheduled at due-reminder, grade push via polling + Pushe

## Settings
- Theme 5 presets + dark mode, dept default but override, UserPreferences table theme_id dark_mode
- Notifications: Global push toggle, SMS fallback toggle mobile field link profile, per-spec mute list enrolled specs toggle POST /api/v1/notifications/mute {spec_id, muted}, Test Push button
- Profile: Student Number read-only, First/Last editable once per semester AuditLog major_edit, supplementary_details 500 optional, mobile/email nullable not visible staff, Save PATCH /api/v1/users/me
- Password change old+new complexity not same last 3, logout other devices checkbox
- Offline Queue: /settings/offline-queue page list IndexedDB queue pending/synced/failed/conflict with retry/delete/resolve, FileCache clear, Delete Local DB recreate, Last sync time, Sync Now button
- Intranet Status: isOnline, isIntranetMode (internal health reachable but external not), isOffline badges, Internal server reachable latency, External reachable, Polling status, Push provider Pushe, Last health check, Recheck button, Info text intranet + SMS opt-in
- About: App version, Backend version /api/health, Device info, Storage usage file cache / 10GB limit

## Offline (V9 Shared Host)
- Read: Workbox runtime cache GET specs/enrollments/resources metadata/curriculum/calendar/forms for offline read
- Write queue only 5 safe types: rating, sticky, ticket create/reply, assignment create/submit, curriculum checkbox -> IndexedDB queue pending -> Workbox Background Sync + setInterval 2min when online
- Finalize enrollment, resource upload, broadcast require online: "برای نهایی‌سازی نیاز به اینترنت است"

## Audit Logs by Student
- honor_status_change, login, failed_login, resource upload, rating, ticket create

END STUDENT V9
