# P11 Student Assignment Tracker - V9 Shared Host (Local Notifications + Polling)

## Routes
/assignments list, /assignments/{id} detail, /assignments/new create

## List Page /assignments
UI View Toggle Kanban / List Kanban 5 columns Pending Submitted Graded Late Missed count cards draggable drag Pending->Submitted requires attachment warning modal "آیا بدون فایل تحویل می‌دهید؟" Allow warn, List table Title Course Due Date Shamsi countdown Status badge Grade Reminder icon, Filters Status multi Course/spec dropdown own enrolled search Due range upcoming/overdue/missed Sort Due asc default Created desc Grade Fab Add, Stats top counts

Data GET /api/v1/assignments?status=&specification_id=&search=&page= student own professor own specs filter Workbox cache, Polling for grade notification via GET /api/notifications/unread every 15s + Pushe PHP curl

## Detail Page /assignments/{id}
UI Header Title large bold Status badge Course+Prof Due Date Shamsi+Gregorian countdown Reminder, Description full, Attachment download preview if PDF, Submission info Submitted At Attachment Grade section if graded Grade large "18/20" green Graded By name Graded At Shamsi Feedback if any else "هنوز نمره‌دهی نشده", Actions Edit if not graded Delete Submit if pending/late button "ثبت تحویل" sets submitted submitted_at now requires attachment? Optional Unsubmit if submitted before due date allow revert to pending, Timeline Created Due Submitted Graded events dates

Data GET /api/v1/assignments/{id}, GET /api/v1/assignments/{id}/attachment/download direct file /uploads/assignments/{student}/{uuid}.pdf

## Create/Edit Page /assignments/new and /assignments/{id}/edit
Form Title required max100 Description max500 Specification dropdown required own finalized enrollments current searchable Due Date Shamsi picker required Reminder Before Hours dropdown 1h 3h 24h 72h default 24 Attachment optional file picker max20MB PDF/DOCX/ZIP Status default pending, Submit saves schedules local notification at due-reminder via Capacitor LocalNotifications no server needed, snackbar "تکلیف ذخیره شد - یادآور تنظیم شد"

API Laravel V9
POST /api/v1/assignments {title, description, specification_id, due_shamsi, reminder_before_hours, attachment} multipart
PATCH /api/v1/assignments/{id}
DELETE /api/v1/assignments/{id}
POST /api/v1/assignments/{id}/submit {attachment optional}
POST /api/v1/assignments/{id}/grade {grade, feedback} professor
GET /api/v1/assignments/{id}/attachment/download

## Offline V9
List cached Workbox, create/edit/delete queued IndexedDB, submit queued, local notification scheduled locally even offline via Capacitor

## Edge V9
Due past on create warning "تاریخ سررسید گذشته است", Reminder time already passed due-reminder < now no local notification scheduled warning, Delete with scheduled cancel via Capacitor cancel, Professor grades deleted 404, Kanban drag graded->pending blocked

## Notifications V9
Local + polling reminder at due - reminder_before_hours + Pushe PHP curl, Overdue push when status becomes late via cron hourly, Grade push when graded via polling + Pushe
