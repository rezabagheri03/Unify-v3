# F08 Ticketing Support - V9 Shared Host (Laravel + Cron)

## Ticket Entity MySQL
Ticket id UUID, student_id FK, department ENUM education technical student_affairs, subject VARCHAR(255), description TEXT, status ENUM open in_progress answered closed default open, assigned_to VARCHAR(32) FK nullable, student_attachments JSON max 3 images 5MB each, staff_attachments JSON any except exe 20MB, created_at updated_at closed_at nullable, escalated_at nullable is_escalated BOOL default 0 escalation_level INT default 0
TicketReply id UUID, ticket_id FK, sender_id FK, body TEXT, attachments JSON, sent_at DATETIME, is_staff BOOL

## Status State Machine
Open -> In Progress (Expert/Admin assigns to self) -> Answered (Expert/Admin replies) -> Closed (Expert/Admin closes)
Student reply when answered -> reverts open, when in_progress stays in_progress but notifies assigned staff, Closed cannot reopen by student must create new related ticket

## Student Flow
Create Page Department dropdown required, Subject max100, Description max2000, Attachments image picker max3x5MB preview thumbnails remove, validation images only mime image/jpeg/png finfo
POST /api/v1/tickets multipart department subject description attachments idempotency key MySQL, creates Ticket status open assigned_to null escalated false
List Tabs Open Answered Closed All, status badge colors open gray in_progress blue answered green closed black, dept badge, last reply Shamsi, assigned_to, escalated red badge
Detail Header ID short Subject Dept badge Status badge Created Shamsi Assigned Escalated Close reason, Description + student attachments images preview lightbox, Timeline TicketReply sorted asc bubbles left avatar student/staff is_staff badge body attachments student images preview staff file download sent Shamsi, Reply Section if closed banner "این تیکت بسته شده" + button "ثبت تیکت مرتبط" navigates new?related_id prefilled [مرتبط با #ID] old subject, If open/answered/in_progress textarea + image picker max3 total per ticket, Send POST /api/v1/tickets/{id}/reply body attachments
Notifications: On staff reply answered -> polling + Pushe to student "پاسخ جدید برای تیکت شما", on close -> polling + Pushe "تیکت شما بسته شد"

## Staff Flow Expert/Admin
Queue Filters Dept own dept Expert all Admin, Status open/in_progress/answered/closed/escalated, Assigned to me/unassigned/all own dept, Search, Table ID Student ID/Name Subject Dept Status Assigned Updated Escalated badge
Detail same timeline but staff actions Assign to me button sets assigned_to self and in_progress, Set In Progress button, Reply text + file any max20MB except exe, Close with reason modal sets status closed closed_at now notifies student
Escalation: Cron hourly Laravel command tickets:escalate checks tickets where status open/in_progress and no staff reply 48h -> is_escalated=1 escalated_at now escalation_level 0->1 (expert->admin) assigned_to null or admin, notify Admin via polling + Pushe "تیکت {id} اسکلیشن شد", If Admin no reply 48h after level1 -> level2 -> notify Owner
Escalated badge red

## Validations
Student images only max5MB each max3 per ticket finfo image, Staff any max20MB block exe bat sh, Subject max100 desc max2000 reply body max2000, Rate limit Student 5 tickets per day via MySQL table

## API Laravel
POST /api/v1/tickets, GET /api/v1/tickets?status=&department=&assigned_to_me=&page=&search=, GET /api/v1/tickets/{id} includes replies, POST /api/v1/tickets/{id}/reply multipart, PATCH /api/v1/tickets/{id}/status {status, close_reason}, PATCH /api/v1/tickets/{id}/assign {assigned_to}

## Offline V9
List cached Workbox, detail cached, create queued IndexedDB with local image staging path, reply queued

## Edge
Reply to closed 403 "تیکت بسته شده - تیکت جدید ثبت کنید", Image 6MB 400 "حجم هر تصویر حداکثر 5 مگابایت", 4th image 400 "حداکثر 3 تصویر", Spam 6 tickets same day 429, Escalation cron race optimistic locking version field Ticket
