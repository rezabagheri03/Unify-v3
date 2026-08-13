# ROLE: DEPARTMENT EXPERT - V9 Shared Host (Laravel + MySQL + Cloud Host)

## Identity
- ID Personnel ID, role expert, department_id NOT NULL, scope own dept enforced via Laravel Policy RLS WHERE department_id = user.department_id

## Permissions
- CAN: Dept scoped: CRUD courses own dept, CRUD specifications (professor, day_of_week, time, location, telegram link, final/midterm exam Shamsi, semester_id), prereq/coreq graph, curriculum charts draft->pending_approval, forms dept (not univ), reply/close tickets own dept text + file any except exe 20MB, targeted messaging Student ID lookup individual/group max 50 via generic error to prevent enumeration, approve/reject student resources own dept, view student list any spec own dept, Excel import/export own dept courses/specs/curriculum
- CANNOT: Set current semester/global phase (Admin), ban, view other depts, final chart approval (Head), univ forms, hard delete, audit full, analytics full

## Dashboard
- Stats: Total courses own dept, active specs current, pending resources approve count, open tickets count, curriculum status draft/pending/approved
- Quick Actions: Add Course, Add Spec, Import Excel, Pending Approvals

## Course & Spec CRUD
- Course: Code unique, Name, Credits 0-6, dept auto own, is_active
- Spec: Form Course dropdown own dept, Professor dropdown own dept, Day sat-sun-mon-tue-wed-thu-fri Persian, Time Start/End HH:MM end>start, Location required, Telegram URL https://t.me/, Final Exam Shamsi required, Midterm optional, Semester ID current+future, is_active toggle
- Validation professor belongs own dept, course belongs own dept
- On edit time/location/day: Diff check, if changed -> notify enrolled via polling + Pushe PHP curl "زمان/مکان درس {course} تغییر کرد"
- On delete: Confirmation typing code, archive enrollments to history, hard delete after, notify enrolled "مشخصه لغو شد"
- Excel Import Specs: Columns Course Code, Professor ID, Day, Time Start/End, Location, Telegram Link, Final Exam Shamsi, Midterm Shamsi, Semester ID - transactional validation via PhpSpreadsheet, rollback if any row error, error report Excel with column خطا red

## Prereq & Coreq Manager
- For each Course list prereq array course_ids and coreq, Add prereq search own dept not self, cycle detection DFS via PHP, prevent circular, Save POST /api/v1/courses/{id}/prerequisites

## Curriculum Chart Editing
- Select entry year 1395-1410, if no chart Create draft
- Editor tree expandable semesters, each course row Code/Name Credits Is Required toggle Prerequisites multi-select Suggested Semester Remove, Add Course search own dept, Import Excel, Save as Draft PATCH, Submit for Approval POST pending_approval notifies Head
- Diff vs last approved highlighted added green removed red

## Forms Repository
- List own dept forms + univ read-only, CRUD own dept Title Description File PDF/DOCX 20MB Signature guide one-liner required Is Active, file to `/uploads/forms/{dept}/{uuid}.pdf`

## Help Desk Tickets
- Filters Open, In Progress, Answered, Closed, Escalated, Assigned to me, Unassigned, Own Dept, Search student id/subject
- Detail timeline TicketReply, student images preview, staff file download, actions Assign to self sets assigned_to self and in_progress, Reply text + file any max20MB except exe, Close with reason, status becomes answered on expert reply

## Targeted Messaging - Anti Enumeration
- Input IDs comma or Excel paste max 50, lookup debounced GET /api/v1/users/{id}?dept=own with generic error "کاربر یافت نشد یا دسترسی ندارید" for not found or not in dept or banned
- If valid shows name + dept + banned badge "بن شده - فقط ادمین می‌تواند پیام دهد" and disables send for Expert (only Admin can message banned)
- Send individual/group subject body POST /api/v1/messages/send recipient_ids array, rate limit 10/min via MySQL

## Approve Student Notes
- Queue pending own dept, preview file, Approve badge expert_approved status approved notifies uploader + enrolled if notify checkbox, Reject with reason

## Excel Import/Export Dept Scoped
- Transactional validation, dept scope enforced

## Offline V9
- View cached courses/specs/tickets cached, CRUD requires online (spec change affects students, must be server validated)

END EXPERT V9
