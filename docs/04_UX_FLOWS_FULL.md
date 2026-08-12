# Unify - Full UX Flows - V9 Shared Host Ready - 600 CS Students
## Complete User Journeys for All Roles - Happy Path + Errors + Offline + Intranet

This document defines EVERY UX flow for V9 (React PWA + Laravel + MySQL + Cloud Host + Host Iran + Polling + Cron). No VPS, no iOS.

---

### 0. Global UX Principles (Applies to All Flows)

**RTL First, LTR for Codes:** All UI RTL, Persian Vazirmatn font, but course codes `CS101`, student numbers `400123456`, Telegram links remain LTR with `dir="ltr"`. Search input placeholder Persian, but typing English code stays LTR.

**Polling, Not WebSocket:** Since shared host cannot run persistent WebSocket (cPanel), all real-time updates via polling `GET /api/notifications/unread?since=lastTimestamp` every 15s foreground, 60s background. UI shows polling status badge: Online green "آنلاین", Intranet yellow "حالت اینترانت - بروزرسانی هر ۱۵ ثانیه", Offline red "آفلاین". For Android, Pushe API via PHP curl provides background push even when polling stopped.

**Offline Queue Visible:** Any action that can be queued (rating, sticky note, ticket create/reply, assignment create/submit, curriculum checkbox, message send) shows immediate optimistic UI + entry in `/settings/offline-queue` with status pending/synced/failed/conflict. Actions requiring online (finalize enrollment, resource upload 50MB, broadcast, ban, set semester) show disabled button + tooltip "برای این کار نیاز به اینترنت است" when offline.

**Honor System Transparent:** Everywhere academic_status shows yellow banner "خوداظهاری: ترم آخر - مسئولیت با شماست". Final semester abuse >2 times shows red flag to Expert.

**IT Handout Physical:** No "forgot password email". All password resets require in-person IT visit + envelope PDF.

**Empty, Loading, Error States:** Every list has 3 states: Loading = skeleton cards (CourseCardSkeleton, FileCardSkeleton), Empty = illustration + Persian message + CTA button, Error = red banner + retry button.

---

### 1. PERSONAS

**Student - Sara, 20, CS 1401, 2nd year:** Uses phone 80% (Android), campus WiFi spotty, needs to download resources offline for subway, enrolls quickly during Phase A, checks exam schedule last minute.

**Professor - Dr. Havand, 45, Teaches Math2:** Uses desktop 70%, uploads PDFs after class, wants to see who downloaded, broadcasts announcement to class before exam.

**Expert - Ms. Ahmadi, Education Dept CS:** Uses desktop, manages 40 courses, 100 specs, approves student notes, replies tickets, imports Excel at start of semester.

**Head - Dr. Karimi:** Approves curriculum charts final, oversees professors not uploading.

**Admin - Mr. Hosseini, IT + Education:** Sets current semester, switches phase enrolling->active (starts 24h grace), bans abusive users, uploads logo.

**Owner - University IT Director:** Bulk imports 600 new students via Excel, prints 600 envelopes, views audit logs when dispute.

**IT Staff - Ali:** Prints envelopes, hands physically, checks ID card.

---

### 2. STUDENT FLOWS (12 Flows)

#### FLOW S01: First Login + Onboarding + IT Envelope

**Goal:** Student receives sealed envelope physically and becomes active user.

**Trigger:** IT hands envelope to Sara (Student Number 400123456 + temp password 12 chars `A8$k2!pL9@qW`)

**Steps:**
1. Sara opens https://unify-cs.ac.ir (Host Iran low ping) on phone.
2. Sees Login page P01 - centered card logo Unify, title "ورود به سامانه یکپارچه", fields Username/Password, show/hide eye, button "ورود", link "رمز را فراموش کرده‌اید؟" -> modal "به صورت حضوری به IT با کارت شناسایی مراجعه کنید".
3. Sara enters 400123456 / `A8$k2!pL9@qW`, taps ورود.
4. System: POST /api/v1/auth/login {username, password} -> Laravel checks is_banned false, checks temporary_password_expires_at (now+7d) not expired, verifies Argon2id Hash::check, rate limit 5/15min per IP via file cache, if fails logs AuditLog failed_login IP UA, returns 401 generic message to avoid enumeration.
5. Success: Sets Sanctum httpOnly secure cookie, returns user JSON with must_change_password=1, first_name null.
6. Frontend guard detects must_change_password true OR first_name null -> Redirect `/onboarding`.
7. Onboarding Page Stepper 2 steps:
   - Step1: First Name required, Last Name required, Supplementary Details optional textarea 500 chars "اطلاعات تکمیلی که میخواهید اساتید ببینند (اختیاری) - مثلا شماره تماس در صورت تمایل". Sara enters "سارا" "احمدی".
   - Step2: Old Password temp field (prefilled masked), New Password, Confirm New Password, live complexity indicator checks min 8, upper, lower, number, special, not same as temp, not in last 3 PasswordHistory table. Checkbox "متوجه شدم باید رمز را تغییر دهم" required.
8. Sara enters new password `Sara@1403!CS`, taps "ثبت و ادامه".
9. System: POST /api/v1/onboarding {first_name, last_name, supplementary_details} + POST /api/v1/password/change {old, new} -> validates old matches temp, new complexity, not in last 3, hashes Argon2id, stores PasswordHistory, sets must_change_password=0, temporary_password_expires_at null, revokes other tokens, creates AuditLog.
10. Success snackbar "خوش آمدید سارا - رمز شما تغییر کرد" -> Redirect `/` Dashboard P02.

**Validations:**
- Username not found -> generic 401 "نام کاربری یا رمز اشتباه است" (no enumeration)
- Temp expired 7 days -> 403 "رمز موقت منقضی شده - به IT مراجعه کنید"
- New password same as temp -> 400 "رمز جدید نمی‌تواند با موقت یکسان باشد"
- Complexity fail -> inline error under field

**Error States:**
- Offline on login: Banner red "برای ورود نیاز به اینترنت است" + button disabled, but if Sara previously logged in and has IndexedDB CachedUser, allow offline login with local check? V9: No, login requires online, show offline message.

**Notifications:**
- On password change, polling will fetch notification to other devices "رمز شما تغییر کرد - اگر شما نبودید به IT اطلاع دهید"

---

#### FLOW S02: Smart Dashboard + Archive + Alerts (P02)

**Goal:** View current semester courses, alerts for conflicts.

**Trigger:** Sara already onboarded, opens `/`

**Steps:**
1. AppBar: Logo, Brand Unify, Semester badge "ترم جاری: 1403-1", Global state badge "حالت: ثبت‌نام", Polling status badge Intranet/Online/Offline, Bell with unread count from GET /api/notifications/unread, Profile avatar.
2. Top: Archive Dropdown - Default "ترم جاری", plus past semesters where Sara has archived enrollments (query SELECT DISTINCT semester_id FROM enrollments WHERE student_id=Sara and status=archived). For new student only current.
3. Alert Banners Stack (top to bottom): Grace countdown (if active), Schedule conflict (if Expert changed time/location of enrolled spec causing overlap), Honor banner yellow "خوداظهاری: ترم آخر", Offline red, Intranet yellow.
4. API calls: GET /api/v1/enrollments?semester=current&status=finalized -> returns spec IDs, GET /api/v1/specifications?ids=... expanded, GET /api/v1/specifications/{specId}/noticeboard?active_only=true for banner.
5. Course Cards List virtualized react-window: Each card header colored hash from professor_id hash hue HSL 70% saturation 50% lightness white text WCAG AA, title bold Course Name, Professor Name, Day+Time Persian "شنبه 8:00-10:00" with clock icon, Location "کلاس 101" with location icon, Credits badge "3 واحد", Exam date small "امتحان نهایی: 1403/04/20", Notice Banner if active high priority first notice title priority color, Footer 3 buttons: Download Resources (icon download) -> navigates /resources?course_id={courseId}&professor_id={profId}, Class Group (icon telegram) -> confirmation dialog "آیا می‌خواهید به گروه تلگرام {course} بروید؟" URL https://t.me/..., window.open external, Details (icon info) -> modal Details.
6. Details Modal tabs Info (Code Credits Dept Professor personnel ID Day Time Location Telegram Link clickable external), Exams (Final Date Shamsi+Gregorian Time Midterm orange badge), Notices (list active notices for this spec), FAQ (list FAQ).
7. Polling: Every 15s GET /api/notifications/unread?since=lastTimestamp - if spec_updated event for enrolled spec time changed, updates card in real-time 15s delay + shows critical banner red "تداخل برنامه به دلیل تغییر {course} - بررسی کنید" + Pushe push via PHP curl if Android.

**Validations:** None.

**Error States:** Loading skeleton cards, Empty no enrollments illustration "برنامه شما خالی است" + button "رفتن به ثبت‌نام" if Phase A else "با آموزش تماس بگیرید", Error retry button.

**Offline:** Cached enrollments + specs + notices via Workbox runtime cache 5min, download resources cached only via Cache API, external telegram link requires online shows offline message if offline.

**Edge:** Spec deleted while viewing dashboard -> polling 15s later card removed + notification "مشخصه {course} لغو شد" + alert banner.

---

#### FLOW S03: Scheduler Phase A Enrollment + Honor + Golden (P03)

**Goal:** Add temporary courses, check conflicts, finalize.

**Trigger:** Global state enrolling, Sara goes to /scheduler/enrolling, or grace period active for finalization only.

**Steps:**
1. Top: Honor System Section - Radio group 4 options Normal 12-20, Conditional max14, GPA_A max24, Final max24 + ignore time/exam, acknowledge checkbox "اینجانب صحت اطلاعات را تایید می‌کنم" required, Button "ثبت وضعیت" POST /api/v1/users/me/academic-status {status, acknowledged=true} stores academic_status_declared + count++ + last_declared + AuditLog honor_status_change, banner current status "وضعیت فعلی: ترم آخر (خوداظهاری)". If not declared, Final Submit disabled tooltip "ابتدا وضعیت آموزشی را انتخاب کنید".

2. Credit Summary: Current temporary credits sum, min/max per declared status, progress bar "18 / 12-20 واحد" green within red over/under, warnings Time conflicts, Exam conflicts, Prereq warnings.

3. Search & Filter: Search bar Name/Code debounce 300ms, Filters Dept, Credits, Day multi-select sat-wed-thu, Time range slider 8-18, Professor search, Sort Name Credits Time.

4. Available Specs List: Virtualized 20 per page infinite scroll GET /api/v1/specifications?semester=current&search=&day=&page=, Each spec card Course Name Code Professor Day Time Location Credits Exam date Add button "افزودن" primary if not in temp else "حذف" secondary.

5. Add logic: On click Add, client checks time overlap day_of_week + time intervals, exam overlap same day Gregorian 2h buffer, prereq check StudentPassedCourse not passed -> modal warning "پیش‌نیاز {course} را پاس نکرده‌اید، ادامه می‌دهید؟" Continue/Cancel, coreq allow, credit limit per honor status, if passes adds to temp list via POST /api/v1/enrollment/temp {spec_id} idempotency key MySQL IdempotencyKeys table, returns temp enrollment.

6. If time overlap and status != final_semester -> error snackbar "تداخل زمانی با {course} {day} {time}" block add. If final_semester and overlap -> warning snackbar "تداخل نادیده گرفته شد (ترم آخر)" allow add.

7. Temporary List Sidebar desktop / Bottom Sheet mobile: List added specs temp total credits each row Course Name Day/Time Delete icon, Shows conflict warnings if spec changed after add (via polling), Button "پاک کردن همه" confirmation.

8. Golden Schedule Button: "پیشنهاد برنامه طلایی" lightbulb icon -> opens modal preferences free days checkboxes, max gap slider 0-6, prefer professors multi-select, prefer morning toggle, Generate button GET /api/v1/golden-schedule?preferFreeDays=thu,fri&maxGap=2&preferProfessors=P1,P2 -> Laravel backtracking PHP with timeout 5s max 1000 combos scoring freeDays*20 -gap*10 +profBonus*15, returns top 15, loading spinner, list 15 suggestion cards header "پیشنهاد 1 - امتیاز 85 - 18 واحد - 2 روز خالی" body specs list day/time/professor footer Apply bulk adds to temp after checking conflicts.

9. Final Submit: Sticky bottom button "نهایی‌سازی" disabled if credit violation honor not declared empty, confirmation modal "آیا از نهایی‌سازی {credits} واحد مطمئنید؟ پس از نهایی‌سازی فقط در مهلت 24 ساعته می‌توانید ویرایش کنید؟" Actually per spec final locks list, POST /api/v1/enrollment/final idempotency key MySQL, on success snackbar "ثبت‌نام نهایی شد" navigates to /scheduler/active (weekly timetable). If grace active, Add button disabled, only Final Submit enabled, banner countdown red "مهلت نهایی‌سازی: 23:59:45".

**Validations:** Credit limit per honor status, time overlap day_of_week, exam overlap same day, prereq warning not block, coreq warning.

**Error States:** Loading skeleton, Empty search no results illustration, Grace active Add disabled.

**Offline V9:** Available specs cached Workbox runtime cache 5min, add/remove temp queued IndexedDB SyncQueue pending -> Workbox Background Sync + setInterval 2min when online, final requires online "برای نهایی‌سازی نیاز به اینترنت است", golden offline Web Worker cached data via Workbox.

**Edge:** Add spec just deleted by Expert 404 on POST temp "این مشخصه حذف شده" refresh list, Credit exactly boundary allow 20 allowed 21 blocked, Prereq warning Continue adds with warning badge, Temporary list 10 specs credit 20 add 11th 3 credits exceed max 20 block "سقف واحد", Polling spec_updated event if spec in temp list time changed mark conflict red.

---

#### FLOW S04: Scheduler Phase B Weekly Timetable (P04)

**Goal:** View finalized schedule as graphical weekly grid.

**Trigger:** Global state active OR enrolling but already finalized -> after finalization show banner "ثبت‌نام شما نهایی شده - منتظر شروع ترم" + view active.

**Steps:**
1. Layout: Week navigation Sat-Wed only optional Thu/Fri toggle if university has Thu classes, Main grid Y time 8-18 half-hour slots X days Sat Sun Mon Tue Wed Thu Fri, Specs placed blocks day/time height proportional duration 1.5h block, Each block Course Name short Professor short Location small color header deterministic hash professor_id white text, Click block Details modal same Dashboard, Read-only no drag-drop, Bottom button "مشاهده برنامه امتحانات" if exam period active or global_state exam navigates to /scheduler/exam.

2. Data: GET /api/v1/enrollments?semester=current&status=finalized specs include day_of_week time_start_end location course name professor name, cached Workbox 5min.

3. Timetable logic: Convert time_start/end to minutes calculate top (start-8*60)/(10*60)*gridHeight, Height (end-start)/(10*60)*gridHeight, Overlap handling if two specs same day overlapping time shouldn't happen unless final_semester show side by side 50% width + red border conflict warning.

4. Polling: Every 15s checks spec change during active phase timetable updates real-time 15s delay + critical banner if conflict.

**Empty:** No finalized -> illustration "برنامه خالی است" + button go to enrollment if still enrolling and grace active else "با آموزش تماس بگیرید"

**Offline:** Cached timetable viewable fully offline Workbox.

---

#### FLOW S05: Scheduler Phase C Exam Mode Flip (P05)

**Goal:** View exam schedule sorted by date.

**Trigger:** Button "مشاهده برنامه امتحانات" appears when global_state exam OR AcademicCalendar exam_period_start within 14 days, always visible but disabled tooltip "هنوز فصل امتحانات شروع نشده" if not exam period.

**Steps:**
1. Container FlipCard Framer Motion Front weekly timetable same Phase B, Back linear exam list sorted final Gregorian asc, Flip trigger Button when front flips to back button text "بازگشت به برنامه هفتگی" when back flips to front, Animation rotateY 0->180 0.6s easeInOut perspective 1000 preserve-3d, Reduced Motion If prefers-reduced-motion fade opacity not rotateY.

2. Back view: List items Each row card Course Name Bold Professor Code Final Exam Date Shamsi Day Persian e.g., شنبه 1403/04/20 + Time + Location, Midterm If exam_date_midterm exists orange badge "میان‌ترم" + date Shamsi location same, Color Final blue badge Midterm orange, Countdown "5 روز مانده" upcoming, Sort By final exam date asc midterm within same course below final, Group by date optional.

3. Data same enrollments finalized specs include exam dates Gregorian + shamsi_original GET enrollments?semester=current&status=finalized includes exam dates via expand, cached.

**Error:** Spec has no final exam date null "تاریخ نهایی ثبت نشده" gray, Many exams 20+ back list scrollable max height 80vh, Flip during loading disable flip button until loaded, Rapid double click debounce 600ms.

**Notifications:** Exam date changes polling 15s + Pushe PHP curl "تاریخ امتحان {course} تغییر کرد"

---

#### FLOW S06: Resource Hub - Browse, Download, Upload, Rate, Sticky, Versions (P06)

**Goal:** Find and download evergreen resources for course+professor.

**Trigger:** Sara taps Download Resources on course card or goes to /resources

**Steps List Page /resources:**
1. Query params course_id professor_id specification_id search sort newest/oldest/rated/downloaded badge filter page
2. Top Search bar + Filter bar Course dropdown Professor dropdown Badge type multi-select Sort dropdown My Uploads toggle, Tabs All Cached (files cached via Cache API) My Uploads, List Virtualized FileCards 20 per page infinite scroll, FileCard detailed cache status icon pin icon, Empty No resources illustration + button upload if student, Fab Button "آپلود جزوه"
3. Data GET /api/v1/resources?course_id=&professor_id=&specification_id=&sort=newest&search=&page= cached Workbox metadata 5min, Cached resources from Cache API FileCacheMeta IndexedDB
4. Actions Click FileCard -> detail, Download button in card direct download without detail, Pin button Pin/unpin file to prevent LRU eviction POST /api/v1/file-cache/{id}/pin {pinned bool} local only IndexedDB, Filter from Dashboard prefilled course+prof

**Detail Page /resources/{id}:**
5. Header Title large bold Author Shamsi date Badge Version Download Count Rating avg + count, Tabs Preview/Info Rating Sticky Note Versions
6. Preview/Info Tab File preview For PDF pdf.js first page preview For DOCX icon + download to view, Description full, Course+Professor evergreen link, Specification context if exists, Buttons Download primary Rating star Add Sticky Note, Cache status If cached "کش شده" green check size last accessed pin toggle, If not cached offline cloud icon + "برای دانلود نیاز به اینترنت است - فایل کش نشده", Versions tab link "مشاهده نسخه‌ها (3 نسخه)"
7. Rating Tab Average star large + count + distribution chart bars 5-1, My Rating section If rated shows my stars edit else rating input 1-5 + submit button shows "شما آپلودکننده هستید" if self, Optional snackbar after 30s viewing triggers rating input highlight
8. Sticky Note Tab Textarea private note max1000 Save Delete Private badge "فقط شما"
9. Versions Tab List versions family sorted version desc row version number upload date Shamsi uploader changelog download button if file exists badge old/new scheduled hard delete date for old versions
10. Data GET /api/v1/resources/{id}, GET /api/v1/resources/{id}/rating/me, GET /api/v1/resources/{id}/sticky-note, GET /api/v1/resources/{id}/versions
11. Actions Download GET /api/v1/resources/{id}/download direct file /uploads/resources/{course}/{prof}/{uuid}.pdf increments download_count caches via Cache API saves to cache dir updates LRU last_accessed success snackbar "دانلود شد - کش شد", Rating POST /api/v1/resources/{id}/rating {rating} updates average via Laravel observer, Sticky POST /api/v1/resources/{id}/sticky-note {note} encrypted via Crypt::encryptString, Delete Sticky DELETE, Pin Local toggle IndexedDB, Upload New Version Only professor/admin visible button modal file picker POST /api/v1/resources/{id}/new-version multipart file + changelog, Offline Detail shows cached info Workbox download cached file via Cache API native viewer rating/sticky queued IndexedDB

**Upload Page /resources/upload:**
12. Form Course dropdown required any course+prof evergreen but student restrict own dept courses, Professor dropdown required professors teaching that course, Title required max255 Description optional max1000 File picker drag-drop area shows file name size mime icon validation, Checkbox "ارسال نوتیفیکیشن به همکلاسی‌ها" default false, Submit button "آپلود" loading, After submit success "جزوه در انتظار تایید است" navigate my-uploads, API POST /api/v1/resources/upload multipart title description course_id professor_id specification_id optional file notify bool, Validation Course+prof required title required file required PDF/DOCX max50MB magic finfo quota 5/day check MySQL 429

**My Uploads Page /resources/my-uploads:**
Tabs Pending Approved Rejected, List FileCards status badge reason if rejected download count if approved, No rating own? Rating allowed but flagged, Actions Edit description? Allow edit title/desc for pending only PATCH

**Edge:** Student uploads 6th file today 429 quota, exe renamed pdf magic bytes fails 400, professor new version while old pending delete allowed old scheduled delete still 30d, file cache full 100MB new 60MB eviction LRU non-protected still not enough error "حافظه کش پر است", resource hard deleted by Admin while viewing detail 404

---

#### FLOW S07: Inbox Messaging (P07)

**Goal:** Read announcements, reply private.

**Trigger:** Sara receives push (polling) new message, bell badge increments unread count.

**Steps List /inbox:**
1. AppBar Title "صندوق پیام" + search + filter, Tabs All Unread Classes broadcast Private System with counts badge, List Virtualized messages sorted sent_at desc infinite 20 per page, Message Row Avatar professor photo placeholder or system icon Sender name bold if unread Subject bold if unread Body preview 80 chars gray Shamsi date small Read dot blue if unread Edited badge small Deleted placeholder italic Priority badge high red, Swipe left mark read/unread, Pull to refresh, Empty No messages illustration, Polling every 15s GET /api/notifications/unread + GET /api/v1/messages?tab= for new
2. Data GET /api/v1/messages?tab=all/unread/classes/private/system&page=&search= Workbox runtime cache 5min, Polling events new_message message_updated message_deleted message_read via polling endpoint, States Loading skeleton rows Offline Cached messages viewable banner offline

**Detail /inbox/{id} Thread View:**
3. Header Subject Sender name date Shamsi priority back button, Body Full body text selectable is_edited badge edited_at tooltip is_deleted placeholder, Thread chain parent->children sorted asc chat bubbles self right blue other left gray body sent_at edited, If broadcast banner "ارسال به کل کلاس {course}", Reply section bottom textarea + Send button if broadcast reply creates private thread to original sender professor, If system reply hidden, If deleted reply hidden placeholder
4. Data GET /api/v1/messages/{id} includes thread children array sorted asc, POST /api/v1/messages/{id}/read on open marks read creates MessageReadStatus
5. Reply Flow Textarea required max2000 Button Send -> POST /api/v1/messages/send {recipient_id OR specification_id for broadcast? Actually reply private recipient_id original sender subject Re: original body parent_message_id=id} idempotency key MySQL IdempotencyKeys, On success new bubble appears list inbox updates polling + Pushe PHP curl to recipient, Edit/Delete Professor only student sees result via polling message_updated/deleted

**Offline V9:** Detail cached Workbox read status queued IndexedDB reply queued

**Edge:** Message deleted while viewing detail polling 15s later updates to placeholder reply disabled, Thread many replies 50+ scrollable, Unread count badge bottom nav updates via polling

---

#### FLOW S08: Ticketing (P08)

**Goal:** Get support for education/technical issues.

**Trigger:** Sara has problem enrollment, goes to /tickets/new

**Steps List /tickets:**
1. AppBar Title "پشتیبانی و تیکت" + New Ticket Fab, Tabs Open Answered Closed All + counts, Filters Department education/technical/student_affairs dropdown search subject, List Virtualized tickets sorted updated_at desc infinite Ticket Row Status badge colors open gray in_progress blue answered green closed black Department badge Subject bold Last reply preview Updated Shamsi Assigned to name if assigned Escalated red badge if is_escalated Attachment icon if has student attachments, Empty No tickets illustration + button New Ticket
2. Data GET /api/v1/tickets?status=&department=&search=&page= student own only Workbox cache, Polling events ticket_updated ticket_replied ticket_closed via polling 15s

**Detail /tickets/{id}:**
3. Header ID short Subject Department badge Status badge Created Shamsi Assigned Escalated badge Close reason if closed, Description Student original description + student attachments images preview thumbnails clickable lightbox, Timeline Vertical timeline TicketReply sorted asc Each reply bubble Left avatar student or staff name badge is_staff blue Body text Attachments student images preview staff file download sent Shamsi small, Reply Section bottom If status closed banner "این تیکت بسته شده" + button "ثبت تیکت مرتبط" navigates /tickets/new?related_id={id} prefilled [مرتبط با #ID] old subject If open/answered/in_progress textarea + image picker max3 total per ticket Send POST /api/v1/tickets/{id}/reply body attachments, Actions No edit/delete for student replies only staff can close

**Create /tickets/new:**
4. Form Department dropdown required education/technical/student_affairs with icons Subject text required max100 Description textarea required max2000 Attachments image picker preview thumbnails remove image button validation, Optional related_id query param If ?related_id present prefill subject "[مرتبط با #{id}] {old subject}" and description link old ticket, Submit button "ثبت تیکت" On success snackbar "تیکت ثبت شد" navigate detail, API POST /api/v1/tickets multipart department subject description attachments images, Validation Department required Subject max100 Description max2000 Images max3 each 5MB images only mime image/jpeg/png finfo, Rate limit 5 per day per student via MySQL table 429 error "حداکثر 5 تیکت در روز"

**Offline V9:** List cached Workbox detail cached create queued IndexedDB with local image staging path reply queued

**Edge:** Reply to closed ticket 403 error shows banner suggests related ticket, Image 6MB error "حجم هر تصویر حداکثر 5 مگابایت", 4th image error "حداکثر 3 تصویر", Escalated badge red tooltip "این تیکت به ادمین اسکلیشن شده", Staff file attachment download requires online if not cached Cache API, Cron escalation hourly Laravel command tickets:escalate checks 48h no staff reply is_escalated=1

**Notifications V9:** On staff reply answered polling + Pushe to student, On student reply open polling + Pushe to staff

---

#### FLOW S09: Curriculum Charts (P09)

**Goal:** Track passed courses for entry year.

**Trigger:** Sara goes to /curriculum

**Steps:**
1. Top Filters Department dropdown auto own dept, Entry Year dropdown approved only 1400 1401 1402 sorted desc, Progress Bar total credits passed/required percentage "85 / 140 واحد پاس شده - 60%"
2. Main Tree expandable by semester/year Each semester node header "ترم 1 - 20 واحد" collapsible Inside List courses leaves Row with Checkbox "پاس شده" checked if StudentPassedCourse passed true Course Code Name Credits Is Required badge "الزامی"/"اختیاری" Prereq icon if has prerequisites, Click row not checkbox opens Course Detail Modal
3. Course Detail Modal Title Course Name Info Code Credits Is Required Suggested Semester Prerequisites list each prereq course code+name status badge "پاس شده" green check if passed else red "پاس نشده" Co-requisites similar Button "مشاهده منابع" navigates resources filtered by course
4. Checkbox Flow: Click checkbox immediate UI toggle + IndexedDB StudentPassedCourseLocal pending + POST /api/v1/curriculum/passed {course_id, passed, entry_year} idempotency key MySQL IdempotencyKeys, OR merge once true stays true unless explicit uncheck confirmation modal "آیا مطمئنید این درس را پاس نکرده‌اید؟", On success server returns updated stats progress bar updates
5. Data GET /api/v1/curriculum-charts?department_id=&entry_year=&status=approved Workbox cache 1h, GET /api/v1/curriculum/passed?entry_year= list passed, Polling for curriculum_chart_updated event banner "چارت به‌روزرسانی شد - رفرش کنید"

**Offline V9:** Tree cached Workbox CacheFirst 1h, checkbox local immediate IndexedDB sync queue

---

#### FLOW S10: Forms, Calendar, NoticeBoard, FAQ (P10)

**Goal:** Access administrative forms and academic calendar.

**Steps Forms /forms:**
1. Tabs Department Forms own dept + University Forms Search bar title List Each form card Title bold Description truncated Signature guide badge "راهنما: امضا مدیر گروه + مهر آموزش" with icon info Download button File size Date Shamsi, Click download GET /api/v1/forms/{id}/download direct file /uploads/forms/{dept}/{uuid}.pdf, download manager via Cache API saves to cache dir, offline indicator cloud not cached check cached, Data GET /api/v1/forms?department_id=&is_university_level=&search=&page= Workbox cache 1h

**Steps Calendar /calendar:**
2. View Toggle Timeline / Calendar, Timeline horizontal scrollable cards sorted start asc clickable date cards color badge per event_type title description truncated start-end Shamsi countdown "5 روز مانده" for upcoming, Calendar View Jalali month/year navigation grid days dots colored for events that day click day shows events list that day bottom sheet, Filters University-wide vs Department Event Type multi-select, Detail Modal Title Description Start/End Shamsi+Gregorian Event Type badge color Countdown Related action button If registration_open "رفتن به ثبت‌نام" navigates scheduler Phase A If exam_period_start "مشاهده امتحانات" navigates exam, Integration banner If calendar says registration close passed but global_state still enrolling show warning to Admin only

**NoticeBoard per Spec /notices/{specId}:** AppBar Course Name + Professor List active notices sorted priority high first then newest Each card Title Content Priority badge color Banner color preview Created Shamsi Expires countdown if set

**FAQ per Spec /faq/{specId}:** List FAQ sorted pinned first accordion Question bold clickable expands Answer Pinned badge "سنجاق شده" yellow Search Q/A

**Common:** All pull to refresh calls polling endpoint, offline banner if offline

---

#### FLOW S11: Assignment Tracker (P11)

**Goal:** Track personal tasks linked to courses.

**Trigger:** Sara goes to /assignments

**Steps List /assignments:**
1. View Toggle Kanban / List Kanban 5 columns Pending Submitted Graded Late Missed count cards draggable drag Pending->Submitted requires attachment warning modal "آیا بدون فایل تحویل می‌دهید؟" Allow warn, List table Title Course Due Date Shamsi countdown Status badge Grade Reminder icon, Filters Status multi Course/spec dropdown own enrolled search Due range upcoming/overdue/missed Sort Due asc default, Fab Add, Stats top counts
2. Data GET /api/v1/assignments?status=&specification_id=&search=&page= student own, cached Workbox, polling for grade notification via GET /api/notifications/unread every 15s + Pushe PHP curl

**Detail /assignments/{id}:**
3. Header Title large bold Status badge Course+Prof Due Date Shamsi+Gregorian countdown Reminder, Description full, Attachment download preview if PDF, Submission info Submitted At Attachment Grade section if graded Grade large "18/20" green Graded By name Graded At Shamsi Feedback if any else "هنوز نمره‌دهی نشده", Actions Edit if not graded Delete Submit if pending/late button "ثبت تحویل" sets submitted submitted_at now, Unsubmit if submitted before due date allow revert, Timeline Created Due Submitted Graded events dates
4. Data GET /api/v1/assignments/{id}, GET /api/v1/assignments/{id}/attachment/download direct file /uploads/assignments/{student}/{uuid}.pdf

**Create/Edit /assignments/new:**
5. Form Title required max100 Description max500 Specification dropdown required own finalized enrollments current searchable Due Date Shamsi picker required Reminder Before Hours dropdown 1h 3h 24h 72h default 24 Attachment optional file picker max20MB PDF/DOCX/ZIP Status default pending, Submit saves schedules local notification at due-reminder via Capacitor LocalNotifications no server needed, snackbar "تکلیف ذخیره شد - یادآور تنظیم شد"
6. API POST /api/v1/assignments {title, description, specification_id, due_shamsi, reminder_before_hours, attachment} multipart, PATCH, DELETE, POST submit, POST grade professor

**Offline V9:** List cached Workbox, create/edit/delete queued IndexedDB, submit queued, local notification scheduled locally even offline via Capacitor

**Notifications V9:** Local + polling reminder at due - reminder_before_hours + Pushe PHP curl, Overdue push when status becomes late via cron hourly, Grade push when graded via polling + Pushe

---

#### FLOW S12: Settings (P12) - Theme, Notifications Mute, Profile, Offline Queue, Intranet Status

**Goal:** Personalize and debug.

**Steps Main /settings:**
Sections list icons Theme & Appearance ->/settings/theme Notifications ->/settings/notifications Profile ->/settings/profile Password ->/settings/password Offline Queue ->/settings/offline-queue Intranet Status ->/settings/intranet-status About & Version

**Theme /settings/theme:** Theme Presets 5 presets color preview circles selected checkmark name, Dark Mode toggle switch, Department Default info "تم پیش‌فرض دانشکده شما: {theme} - شما می‌توانید تغییر دهید", Preview area sample CourseCard with selected theme, Save POST /api/v1/users/me/preferences {theme_id, dark_mode} via Laravel, Offline saved locally IndexedDB immediate

**Notifications /settings/notifications:** Global Push toggle Enable/disable all push stored local + server DeviceToken is_active, SMS Fallback toggle "دریافت SMS برای اطلاعیه‌های حیاتی" checkbox shows mobile field if no mobile set link to profile to add mobile mobile stored but not visible staff only for SMS Kavenegar, Per-Spec Mute List enrolled specs current semester with toggle mute/unmute per spec search Each row spec course name professor mute toggle POST /api/v1/notifications/mute {specification_id, muted}, Test Push button POST /api/v1/notifications/test sends test push via polling + Pushe to own devices

**Profile /settings/profile:** Shows Student Number read-only First Name editable once per semester Last Name editable once per semester Department read-only Academic Status Declared current with banner Supplementary Details textarea optional max500 "اطلاعات تکمیلی که می‌خواهید اساتید ببینند (اختیاری) - مثلا شماره تماس در صورت تمایل" Mobile field nullable not visible staff Email nullable not visible staff Save PATCH /api/v1/users/me, Validation Name max100 supplementary max500 mobile regex Iran email regex Once per semester edit limit for first/last name enforced via last edit timestamp check error "نام فقط یک بار در هر ترم قابل ویرایش است"

**Password /settings/password:** Form Old New Confirm Complexity indicator Checkbox "خروج از سایر دستگاه‌ها" default true Button "تغییر رمز" success snackbar, POST /api/v1/password/change

**Offline Queue /settings/offline-queue:** Page shows IndexedDB syncQueue list with status pending/syncing/synced/failed/conflict Columns Entity Type icon Action Summary Created Shamsi Status badge Attempts Last Error Actions Retry failed Delete pending Cancel Resolve conflict opens conflict resolver modal Top stats Pending count Last sync time Button "همگام‌سازی اکنون" forces sync if online via Workbox Background Sync, Button "پاک کردن کش فایل‌ها" clears Cache API except pinned/protected Button "حذف و بازسازی دیتابیس لوکال" deletes IndexedDB and re-fetches from server

**Intranet Status /settings/intranet-status:** Shows connectivity isOnline isIntranetMode isOffline badges icons Details Internal server reachable true/false latency External reachable true/false Polling connected true/false with url Push provider Pushe Last health check, Button "بررسی مجدد اتصال" triggers health check GET /api/v1/health, Info text intranet explanation "در حالت اینترانت، اعلان‌ها از طریق پولینگ هر ۱۵ ثانیه + پوشه اندروید ارسال می‌شود - برای اطلاعیه‌های حیاتی SMS را فعال کنید", SMS opt-in status

**About & Version:** App version Build number Backend version from /api/v1/health Device info User ID Role Department Last login Shamsi Storage usage file cache size / 10GB Shop plan limit

**Offline V9:** All settings pages work offline except password change profile name edit requires online? Actually profile edit queued IndexedDB offline queue page local only

---

### 3. PROFESSOR FLOWS (6 Flows)

#### FLOW P01: Dashboard + Student List + Resource Management

**Goal:** See own specs, students, resources.

**Steps /professor:** AppBar same + archive toggle past read-only, Stats Total Specs Current Total Enrolled Sum Total Resources Uploaded Avg Rating, Spec Cards Course Spec with enrolled count resource count pending approval count avg rating Buttons Students Resources Messages NoticeBoard FAQ, Quick Actions Upload Resource View Pending Approvals Broadcast Message, Data GET /api/v1/professor/specifications?semester=current (own only) GET /api/v1/professor/stats cached offline viewable but stats require online

**Students List /professor/specs/{specId}/students:** Header Course Name + Spec details Day/Time/Location, Table Student ID searchable Name Academic Status Declared with honor flag icon final_semester Supplementary Details free text if contact Enrollment Status finalized Enrolled At Shamsi Banned badge, Actions per row Send Private Message button opens compose modal, Export Excel button exports list to Excel own spec, Search name/id, Data GET /api/v1/specifications/{specId}/students professor own spec only, Offline cached Workbox

**Resources Pages /professor/resources:** List Own Resources Tabs My Resources approved own + Pending Student Notes queue, My Resources FileCards own with version rating download count actions Edit Description Upload New Version Request Delete, Pending Queue List pending own course+prof uploader student ID/name title file preview button direct file temp path Approve/Reject buttons Approve badge professor status approved notifies student + enrolled if notify checkbox via polling + Pushe PHP curl, Upload Center Form Course dropdown own dept auto prof=self Title required Description optional File PDF/DOCX max50MB Notification checkbox default true "ارسال اعلان به دانشجویان این درس" Submit POST /api/v1/resources/upload immediate approved badge professor file to /uploads/resources/{course}/{prof}/{uuid}.pdf AuditLog, Offline requires online (file large), Detail Same student detail but extra rating distribution download count no who preview, Actions Edit desc Upload New Version Request Delete

**Edge V9 Shared Host:** Professor tries view other professor spec student list 403, Upload exe renamed pdf blocked magic finfo, Broadcast spam 2 within 10min 429, Edit message after push placeholder push irreversible info in edit modal

---

#### FLOW P02: Class Messaging Broadcast + Private (P07 but Professor side)

**Goal:** Announce to class, reply private.

**Steps /professor/messages:** Tabs Broadcast History messages sent to specs Private Chats 1-to-1 threads with students, Broadcast History List broadcast messages sent each shows spec course subject body preview sent at edited badge deleted placeholder edit/delete buttons if own, Private Chats List threads with students each thread student name last preview unread count click thread chat bubbles, Compose Buttons New Broadcast select spec dropdown own specs subject body rate limit 1 per 10min via MySQL cache table, New Private search student ID/name own enrolled students subject body, Thread view chat same student but with edit/delete own messages, Data GET /api/v1/messages?sent_by_me=true&tab=broadcast/private POST /api/v1/messages/send with specification_id for broadcast or recipient_id for private PATCH edit DELETE soft delete, Polling for new replies every 15s

---

### 4. EXPERT FLOWS (8 Flows)

#### FLOW E01: Course & Spec CRUD + Change Alerts

**Goal:** Define courses and offerings for CS dept.

**Steps /expert/courses:** List Table Code Name Credits Is Active Spec Count Actions Edit/Delete/View Specs Search Export Import Add Course button, Create/Edit Form Code unique Name Credits 0-6 Is Active toggle Dept auto own read-only Expert, Validation Code unique via API check, On save POST /api/v1/courses or PATCH AuditLog

**Specs /expert/specifications:** List Table Code/Name Professor ID/Name Day Persian Time Start-End Location Exam Final Date Shamsi Is Active Enrolled Count Actions Edit/Delete/View Students/Resources, Filters Semester current+archive Course Professor Day multi-select Search Export Import Add Spec button, Create/Edit Form Course dropdown own dept required Professor dropdown own dept required Day sat-sun-mon-tue-wed-thu-fri required Time Start/End HH:MM end>start Location required Telegram Link URL optional https://t.me/ Final Exam Shamsi required Midterm optional Semester ID current+future required Is Active toggle, On save Validation professor belongs own dept course belongs own dept time overlap same professor warning "استاد {name} در {day} {time} قبلا کلاس دارد", On edit time/location/day confirmation modal "این تغییر باعث ارسال اعلان به دانشجویان ثبت‌نام شده می‌شود - ادامه؟" lists affected count, Change Alert polling + Pushe PHP curl to enrolled, On delete confirmation typing code archive enrollments notification, Import File drop Excel template download Preview table validation errors Confirm Import POST /api/v1/import/specifications multipart returns validation report or success

**Edge:** Time overlap same professor same day, delete spec with enrollments confirmation typing course code

---

#### FLOW E02: Prerequisite Manager

Select Course dropdown own dept, Shows Current Prerequisites list Codes/Names Current Co-requisites list, Add Prereq Search own dept not self cycle detection DFS via PHP API, Add Co-req Search, Graph View optional nodes edges, Save POST /api/v1/courses/{id}/prerequisites {prereqIds, coReqIds}, Cycle detection error "ایجاد چرخه پیش‌نیاز ممنوع است: {cycle path}"

---

#### FLOW E03: Curriculum Chart Editor Draft -> Pending

**Goal:** Create curriculum for entry year 1401.

**Steps /expert/curriculum:** List Entry Year Status Version Last Updated Approver Actions Edit/View/Submit/Delete Draft Add New Entry Year, Editor Header Entry Year Status Version Last Updated Tree editor expandable semesters 1-12 each semester courses list drag-drop dnd-kit Course Row Code/Name Credits Is Required toggle Prerequisites multi-select Suggested Semester Remove Add Course Search Import Excel, Save as Draft PATCH, Submit for Approval POST pending_approval notifies Head via polling + Pushe, Diff View vs last approved added green removed red

---

#### FLOW E04: Forms Management

List own dept forms + univ read-only, CRUD own dept Title Description File PDF/DOCX 20MB Signature guide one-liner required Is Active toggle, On save file to /uploads/forms/{dept}/{uuid}.pdf

---

#### FLOW E05: Tickets Help Desk

List Filters Status Assigned to me/unassigned/all own dept Department Search Sort, Table ID Student ID/Name Subject Dept Status Assigned Updated Escalated badge, Click detail, Detail Timeline student info sidebar Assign to me sets assigned_to self in_progress, Reply text + file any max20MB except exe, Close with reason, status answered on expert reply

---

#### FLOW E06: Targeted Messaging Anti-Enumeration

Input IDs comma Excel max 50 Add list ID resolved name status valid/invalid/banned Lookup debounced GET /api/v1/users/{id}?dept=own generic error "کاربر یافت نشد یا دسترسی ندارید" for not found or not in dept, If valid shows name + banned badge "بن شده - فقط ادمین می‌تواند پیام دهد" disables send for Expert, Compose Subject Body Send POST recipient_ids array Rate limit 10/min MySQL

---

#### FLOW E07: Pending Resources Approval

List pending own dept Title Uploader ID/Name Course+Prof Upload date Preview Approve/Reject

---

#### FLOW E08: Excel Import/Export

Tabs Import Type Courses Specs Curriculum Calendar File drop Template Download Preview Confirm, Export Type Dept auto own Semester Download GET /api/v1/export/{type}

---

### 5. HEAD FLOWS (2 Flows)

#### FLOW H01: Final Chart Approval

List pending_approval own dept Entry Year Submitted At By Expert Name Version Actions Preview/Approve/Reject, Preview tree + diff vs last approved added green removed red modified yellow, Approve button "تایید نهایی و انتشار" confirmation typing entry year POST /api/v1/curriculum-charts/{id}/approve status approved approved_at now approver self version increment notifies Expert + students dept entry year low polling + Pushe, Reject button reason required POST reject status draft notifies Expert with reason

#### FLOW H02: Professor Oversight

Table Professors ID Name Spec Count Current Resource Count Current Semester Last Upload Date Shamsi Status green >=1 resource yellow 0 but has specs red no specs Actions View Specs/Resources Send Reminder, Detail professor ID Tabs Specs Current Resources Current Action Send Reminder Message prefilled recipient professor, Spec Override edit/delete any spec own dept same change alert polling + Pushe

---

### 6. ADMIN FLOWS (6 Flows)

#### FLOW A01: Define New Semester + Switch Phase + Grace

Current Semester Card Name Start/End Shamsi Global State badge Grace Ends countdown History, Define New Semester Form Name e.g., 1403-2 Start Shamsi End Shamsi Is Current default true On submit POST /api/v1/admin/semesters with confirmation typing name Validation no active grace name unique Triggers soft hide old specs is_active 0 enrollments finalized->archived temp hard deleted resources untouched notification via polling + Pushe to all
Switch Phase Form Radio enrolling/active/exam confirmation typing semester name warning enrolling->active starts 24h, Semester List Table All semesters name start/end is_current badge global_state actions Set Current Edit Dates View Specs/Enrollments

#### FLOW A02: User Management Ban/Unban

Search ID/name/role/dept/banned Filters Role multi Dept multi Banned toggle Academic Status Sort Table ID Name Role badge Dept Academic Status with honor flag Last Login Banned badge Actions View/Ban/Unban Export Excel Pagination 50, Detail ID Name Role Dept Academic Status Declared count last declared Created Last Login Banned status reason Supplementary Details Mobile/Email hidden per privacy even Admin cannot see mobile/email unless student included in supplementary, Enrollments current+archived Resources Uploaded Tickets Created Audit Logs Recent, Ban Modal reason required max500 Expiry date Shamsi optional permanent else date confirmation typing user ID POST /api/v1/admin/users/{id}/ban {reason, expires_at_shamsi} sets is_banned=1 banned_reason banned_at now banned_by self revokes tokens AuditLog notification polling + Pushe "حساب شما بن شد: {reason}" Unban button

#### FLOW A03: Escalated Tickets

Filters Escalated true Level 1 Admin Level 2 Owner Assigned to me/unassigned Dept Status Search Table same Expert but univ-wide, Detail same Expert detail but reassign to Expert dept or self close any reply file escalation history

#### FLOW A04: Branding Logo + Univ Forms + Calendar

Current Logo preview File info size dimensions Upload drag-drop PNG/SVG max2MB preview new Save Brand Name field max50 default Unify POST /api/v1/admin/branding/logo multipart file + brand_name sanitized SVG stores /uploads/branding/logo.png SystemConfig logo_path AuditLog polling to all clients reload logo ?v=timestamp, Forms University List Table Title Desc File size Guide Is Univ true Is Active Actions Edit/Delete/Download Tabs University + Dept All read-only Add/Edit same Expert but is_university_level true checkbox, Calendar University List Table Title Desc Start/End Shamsi Event Type badge Is Univ true Dept Actions Tabs Univ + All Depts Add/Edit same Expert but univ true option dept dropdown if not univ

#### FLOW A05: Final Resource Approval + Hard Delete

List pending + expert_approved needing final, Actions Approve admin_approved badge Reject reason Hard Delete former professor notes button Hard Delete confirmation typing title + reason DELETE /api/v1/admin/resources/{id}/hard-delete hard deletes file content /uploads immediately AuditLog, LRU cleanup cron daily to keep under 10GB Shop limit

#### FLOW A06: University-wide Targeted Messaging

Same Expert but no dept restriction max 100 IDs per group can message banned Admin can message banned, Compose subject body send rate limit 20/min

---

### 7. OWNER FLOWS (4 Flows)

#### FLOW O01: Manual Add + Bulk Import 600 Students

Form ID unique live First Last Role dropdown student/professor/expert/head/admin/owner Department dropdown required staff Academic Status dropdown optional, On Save POST /api/v1/owner/users generates temp 12 chars Str::random hashed Argon2id must_change_password=1 expires 7d creates user logs AuditLog returns envelope PDF download dompdf, Bulk Import Template Download Excel Persian headers sample row Upload drag-drop Excel max5MB max2000 rows preview first10 Validation transactional ID uniqueness within file + DB role enum dept exists academic status enum If errors Error report Excel download column خطا red highlight table errors row+error If success Success count + ZIP download envelopes PDFs per user table created ID/Name/Role Rate limit 1 per 10 min

#### FLOW O02: Password Reset Envelope (IT Handout Core)

Search ID shows profile card ID/Name/Role/Dept Last Reset At/Count Reset Button modal Reason required "درخواست حضوری با کارت شناسایی" Checkbox receipt signed optional Confirm typing user ID POST /api/v1/owner/users/{id}/reset-password {reason, receipt_signed} generates new temp 12 chars invalidates all sanctum tokens must_change_password=1 expires 7d logs AuditLog is_suspicious if >2 per month returns envelope PDF download immediate dompdf not stored, History table recent resets for this user, Envelope PDF University logo Title "پاکت رمز موقت سامانه Unify" Name ID Username Temp Password large monospace QR username+temp Instructions date operator warning 7 days A5

#### FLOW O03: Audit Logs Viewer

Filters User ID Action multi deletion/major_edit/password_reset/role_change/ban/honor_status_change/final_semester_abuse_flag/login/failed_login Resource Type Resource ID Timestamp From/To Shamsi pickers Is Suspicious toggle IP search, Table Timestamp Shamsi+Gregorian sortable Actor ID/name Action badge color Resource Type/ID IP User Agent truncated Details button Suspicious red badge, Details Modal Decrypted details JSON pretty diff old/new via Crypt::decryptString, Export Excel/CSV filtered requires reason modal AuditLog export action itself, Pagination 50

#### FLOW O04: Analytics + Super Read-Only

Filters Semester Dept Date Range Role, KPI Cards DAU WAU MAU Total Users Resources Downloads Avg Session Ticket Avg Response Assignment Submission Curriculum Checkbox Completion Honor Abuse Flags, Charts Active Users Line Downloads per Dept Bar Top Resources Table top10 Top Professors Ticket Response Histogram Honor Pie Intranet Mode Stats % polling vs Pushe Storage Usage donut per dept Failed Login line Tables Flagged Students list ID/Name/Flag Type/Count/Last Declared/Action Resolve Export Full CSV optional PII requires reason audit, Super Read-Only Banner red "حالت فقط خواندنی مالک سیستم" Sidebar all roles pages read-only links Student Dashboard view as any student ID param Professor Dashboard view as any professor etc, Student View As Input Student ID navigates /owner/system/read-only/student/{id}/dashboard shows student dashboard read-only disabled actions watermark فقط خواندنی

---

### 8. IT DEPARTMENT FLOWS (2 Flows)

#### FLOW IT01: Initial Distribution 600 Students

Owner bulk imports 600 via Excel -> generates ZIP 600 envelopes PDFs dompdf + QR, IT downloads ZIP, prints each PDF, folds, seals envelope with university stamp, writes Student Number outside, Student comes in-person with ID card to IT desk, IT verifies ID vs Student Number + Name, IT hands sealed envelope physically, student signs receipt logbook physical + IT can log in system via Owner password reset page reason "Initial handout signed", Student goes home logs in with temp forced onboarding + change password

#### FLOW IT02: Forgot Password

Student forgets, comes in-person with ID card, IT verifies ID, asks Owner to search Student ID in Owner dashboard Password Reset, Owner clicks Reset enters reason "Forgot - in-person ID verified" generates new envelope PDF dompdf, IT prints seals hands student signs, Old sessions revoked, must_change_password=1 expires 7d

---

### 9. CROSS-CUTTING FLOWS

#### FLOW C01: Global Search (Future Enhancement for 600 CS)

Trigger CMD+K or search icon, Modal search courses, resources, tickets, assignments, messages, Shows results grouped, Navigate on click, API GET /api/v1/search?q=, Offline cached search via IndexedDB?

#### FLOW C02: Theme + Dark Mode

Settings -> Theme 5 presets color preview circles selected checkmark name, Dark Mode toggle switch, Department Default info "تم پیش‌فرض دانشکده شما: {theme}", Preview area sample CourseCard, Save POST /api/v1/users/me/preferences {theme_id, dark_mode}, Offline saved locally IndexedDB immediate, Polling for theme? No

#### FLOW C03: Offline Queue Management

Settings -> Offline Queue page list IndexedDB syncQueue status pending/syncing/synced/failed/conflict Columns Entity Type icon Action Summary Created Shamsi Status badge Attempts Last Error Actions Retry failed Delete pending Cancel Resolve conflict opens conflict resolver modal OR merge, Top stats Pending count Last sync time Button "همگام‌سازی اکنون" forces sync if online via Workbox Background Sync, Button "پاک کردن کش فایل‌ها" clears Cache API except pinned/protected Button "حذف و بازسازی دیتابیس لوکال" deletes IndexedDB and re-fetches

#### FLOW C04: Intranet Detection + Polling Status

Client periodically GET /api/v1/health internal (same domain) + fetch https://8.8.8.8 or https://google.com no-cors, If internal reachable but external not -> isIntranetMode=true, App state isOnline isIntranetMode isOffline, UI Badge top header shows "حالت اینترانت" yellow if intranet "آفلاین" red if offline "آنلاین" green if online, When isIntranetMode true increase polling to 10s show info "اینترنت بین‌الملل قطع است - اعلان‌ها هر ۱۵ ثانیه بروز می‌شود", Settings -> Intranet Status page shows details Internal reachable latency External reachable Polling connected Push provider Pushe Last health check Button Recheck

#### FLOW C05: File Cache LRU 10GB Shop Limit

File download via Cache API saves to cache dir /uploads cache? Actually browser Cache API, updates last_accessed IndexedDB FileCacheMeta, Eviction via Workbox LRU 100MB? For shared host 10GB limit for server files, not client. Server side LRU cleanup cron daily checks /uploads/resources size > 8GB (80% of 10GB plan) -> deletes least recently downloaded files that are not protected (professor badge) until size < 7GB, notifies? No, silent.

---

### 10. Error Handling Patterns (All Flows)

- 401 Unauthorized -> Redirect /login + clear token + snackbar "نشست شما منقضی شد - دوباره وارد شوید"
- 403 Forbidden -> Page /403 with message "دسترسی ندارید" + back button
- 404 Not Found -> Page /404 illustration "صفحه یافت نشد" + button go dashboard
- 409 Conflict (enrollment time conflict, idempotency duplicate returns previous response) -> Show conflict resolver modal with server state vs local, options Keep Server / Keep Mine / Merge
- 422 Validation -> Inline field errors Persian under field, e.g., "تاریخ شمسی نامعتبر"
- 429 Rate Limit -> Snackbar "تعداد درخواست زیاد - {Retry-After} ثانیه صبر کنید" + Retry-After header
- 500 Server Error -> Snackbar red "خطای سرور - لطفا بعدا تلاش کنید" + Retry button + log to Sentry optional
- Offline -> Red banner "آفلاین" + disabled buttons with tooltip "برای این کار نیاز به اینترنت است" + queued actions show pending badge in offline queue

---

END FULL UX FLOWS V9 - 600 STUDENTS - SHARED HOST READY
