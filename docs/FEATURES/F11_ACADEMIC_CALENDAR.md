# F11 Academic Calendar - V9 Shared Host

## Data Model MySQL
AcademicCalendar id UUID, title VARCHAR(255), description TEXT, start_date_g DATETIME, end_date_g DATETIME, shamsi_original_start/end VARCHAR(10), event_type ENUM registration_open registration_close semester_start semester_end exam_period_start exam_period_end holiday other, is_university_wide BOOL, department_id FK nullable, color_code VARCHAR(7), created_by FK

## Event Types Colors
registration_open green #4CAF50, registration_close red #F44336, semester_start blue #2196F3, semester_end purple #9C27B0, exam_period_start orange #FF9800, exam_period_end red, holiday gray #9E9E9E, other light blue #03A9F4, overridable

## Roles
Expert CRUD own dept is_university_wide false + dept own, Head same, Admin CRUD univ + any dept, Student view timeline current semester own dept + univ

## Flow Admin/Expert
List calendar events filtered semester current (start within semester range) + dept, Create Title required Description optional Start Date Shamsi picker required End Shamsi required end>=start Event Type dropdown is_university_wide checkbox Admin only + dept dropdown if not univ Color picker optional, Validation Shamsi valid via Morilog\Jalali, overlap warning same type overlapping
Save creates row triggers cron jobs for notifications 7-day advance and 24h advance (Laravel Scheduler daily), Edit/Delete AuditLog notifications to affected if registration/exam push polling + Pushe "تقویم آموزشی تغییر کرد: {title}"

## Flow Student
Two view modes Timeline and Calendar: Timeline horizontal scrollable cards sorted start asc clickable date cards color badge title desc truncated start-end Shamsi countdown "5 روز مانده" for upcoming "در حال برگزاری" current "پایان یافته" past, Calendar View Jalali month/year navigation grid days dots colored for events that day click day shows events list bottom sheet, Filters University-wide vs Department Event Type multi-select, Detail Modal Title Description Start/End Shamsi+Gregorian Event Type badge color Countdown Related action button If registration_open -> "رفتن به ثبت‌نام" navigates scheduler Phase A, If exam_period_start -> "مشاهده امتحانات" navigates exam, Integration banner If calendar says registration close passed but global_state still enrolling show warning to Admin only student normal

## Scheduler Integration
Registration period dates drive Phase A availability: When AcademicCalendar registration_open to registration_close range Phase A should be active. If global_state still enrolling but calendar says registration_close passed show warning banner to Admin "تقویم می‌گوید ثبت‌نام بسته شده ولی فاز هنوز Enrolling است - آیا می‌خواهید به Active تغییر دهید؟" with shortcut button
Exam period dates trigger exam schedule display: When event_type exam_period_start date reached now>=start show "View Exam Schedule" button enabled even if global_state not exam, Button enabled if either global_state exam OR calendar exam period active
Semester dates control spec activation: Semester.start/end already defined but calendar semester_start/end should align warn if mismatch

## Notifications V9 Polling + Cron
Cron daily checks upcoming events: 7-day advance warning registration_open if now = start-7d push to all students own dept or all if univ "7 روز تا شروع ثبت‌نام {title}" via polling + Pushe PHP curl, 24h reminder before registration_close if now = end-24h for registration_close event_type push critical "24 ساعت تا پایان ثبت‌نام", Exam date notifications to enrolled on exam_period_start push "فصل امتحانات شروع شد", Holiday notifications across all courses on holiday start push low "فردا تعطیل: {title}", Polling for real-time calendar updates when event created/edited/deleted broadcast to relevant dept via polling

## API Laravel
GET /api/v1/academic-calendar?semester=current&department_id=&is_university_wide=&event_type=&page=
GET /api/v1/academic-calendar/{id}
POST /api/v1/academic-calendar Expert/Admin {title, description, start_shamsi, end_shamsi, event_type, is_university_wide, department_id, color_code}
PUT /api/v1/academic-calendar/{id}
DELETE /api/v1/academic-calendar/{id}

## Offline V9
Calendar events cached Workbox 1h, timeline viewable offline, detail cached
