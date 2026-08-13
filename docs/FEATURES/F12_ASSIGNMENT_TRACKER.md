# F12 Assignment & Quiz Tracker - V9 Shared Host

## Data Model MySQL
AssignmentTracker id UUID, student_id FK, specification_id FK, title VARCHAR(255), description TEXT, due_date_g DATETIME, shamsi_original VARCHAR(10), reminder_before_hours INT default 24, status ENUM pending submitted graded late missed, attachment_path TEXT nullable /uploads/assignments/{student}/{uuid}.pdf, grade FLOAT nullable graded_by FK nullable graded_at nullable submitted_at nullable created_at DATETIME local_notification_scheduled BOOL

## Roles
Student Full CRUD own submit view grade, Professor View submissions for own specs grading, Expert/Admin read-only

## Student Flow
List Page Kanban/List toggle Kanban 5 columns Pending Submitted Graded Late Missed count cards draggable drag Pending->Submitted requires attachment warning modal "آیا بدون فایل تحویل می‌دهید؟" Allow warn, List table Title Course Due Date Shamsi countdown Status badge Grade Reminder icon, Filters Status multi Course/spec dropdown own enrolled search Due range upcoming/overdue/missed Sort Due asc default Created desc Grade Fab Add, Stats top counts
Detail Header Title large bold Status badge Course+Prof Due Date Shamsi+Gregorian countdown Reminder, Description full, Attachment download preview if PDF, Submission info Submitted At Attachment Grade section if graded Grade large "18/20" green Graded By name Graded At Shamsi Feedback if any else "هنوز نمره‌دهی نشده", Actions Edit if not graded Delete Submit if pending/late button "ثبت تحویل" sets submitted submitted_at now requires attachment? Optional, Unsubmit if submitted before due date allow revert to pending, Timeline Created Due Submitted Graded events dates
Create/Edit Form Title required max100 Description max500 Specification dropdown required own finalized enrollments current searchable Due Date Shamsi picker required Reminder Before Hours dropdown 1h 3h 24h 72h default 24 Attachment optional file picker max20MB PDF/DOCX/ZIP Status default pending, Submit saves schedules local notification at due-reminder via Capacitor LocalNotifications, snackbar "تکلیف ذخیره شد - یادآور تنظیم شد"

## Professor Flow Grading
Page "تکالیف دانشجویان" professor dashboard Filter by spec own specs status submitted search student List Student ID Name Title Due Submitted At Attachment download Status Grade input, Grade Form grade float 0-20 Iranian scale and feedback optional POST /api/v1/assignments/{id}/grade {grade, feedback} sets status graded grade graded_by self graded_at now notifies student via polling + Pushe

## Reminders V9 Polling + Local
On create schedule Capacitor LocalNotifications at due_date - reminder_before_hours local scheduling no server needed, so fires even offline/no internet, Creates local notification id = assignment id hash, If reminder changed reschedule, If deleted cancel, Also server cron schedules polling + Pushe push at same time for online, local is fallback for intranet/offline
Overdue alerts cron hourly checks due_date < now and status pending -> status late notification "تکلیف {title} دیر شد" via polling + Pushe, Missed after 7 days overdue auto missed, Grade notification via polling + Pushe when graded

## API Laravel
GET /api/v1/assignments?status=&specification_id=&search=&page= (student own professor own specs filter)
GET /api/v1/assignments/{id}
POST /api/v1/assignments {title, description, specification_id, due_shamsi, reminder_before_hours, attachment} multipart
PATCH /api/v1/assignments/{id}
DELETE /api/v1/assignments/{id}
POST /api/v1/assignments/{id}/submit {attachment optional}
POST /api/v1/assignments/{id}/grade {grade, feedback} professor
GET /api/v1/assignments/{id}/attachment/download

## Offline V9
List cached Workbox, create/edit/delete queued IndexedDB, submit queued, local notification scheduled locally even offline via Capacitor

## Validation
Title max100 desc max500 due Shamsi valid future? Allow past but warn due past status late, Reminder enum 1,3,24,72, Attachment max20MB PDF/DOCX/ZIP, Grade 0-20, Spec must be own enrolled

## Edge
Due past on create warning "تاریخ سررسید گذشته است", Reminder time already passed due-reminder < now no local notification scheduled warning "زمان یادآور گذشته", Delete with scheduled cancel, Professor grades deleted 404, Kanban drag graded->pending blocked "نمی‌توان نمره‌دهی شده را برگرداند"
