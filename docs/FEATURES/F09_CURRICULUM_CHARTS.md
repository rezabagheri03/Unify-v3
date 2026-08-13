# F09 Interactive Curriculum Charts - V9 Shared Host

## Data Model MySQL
CurriculumChart id UUID, department_id FK, entry_year INT, chart_data JSON MySQL 8 JSON type, status ENUM draft pending_approval approved, approver_id FK nullable, approved_at DATETIME, version INT, UNIQUE(dept, entry_year, version)
StudentPassedCourse id UUID, student_id FK, course_id FK, passed BOOL, grade FLOAT nullable, entry_year INT, created_at DATETIME, UNIQUE(student_id, course_id)

## Roles
Expert Upload/modify chart each entry year creates draft submit pending_approval, Head final approval pending->approved, Student view approved chart own entry year checkbox passed cloud sync, Admin/Owner read-only

## Upload Edit Flow Expert
Select dept auto own, entry year 1395-1410, If no chart Create new draft opens editor
Editor Tree expandable semesters 1-12 each course row Code/Name Credits Is Required toggle Prerequisites multi-select Suggested Semester Remove Add Course search own dept Import Excel Upload Excel template Entry Year Course Code Is Required بله/خیر Prerequisites comma codes Suggested Semester, Validation Course exists own dept cycle detection DFS prevent circular
Save as Draft PATCH status draft version 1, Submit for Approval POST pending_approval notifies Head dept "چارت ورودی {year} در انتظار تایید نهایی" polling + Pushe

## Approval Flow Head
List pending_approval own dept, Preview tree + diff vs last approved version added green removed red modified yellow, Approve "تایید نهایی و انتشار" confirmation typing entry year POST approve status approved approved_at now approver self version increment notifies Expert + students dept entry year low polling + Pushe, Reject reason required back to draft notifies Expert

## Student View Flow
Select major auto own dept, entry year dropdown approved only, Progress Bar total credits passed/required percentage "85 / 140 واحد پاس شده - 60%", Main Tree expandable semester nodes list courses leaves Row checkbox passed Course Code Name Credits Is Required badge Prereq icon Click row modal Course Detail Title Code Credits Is Required Suggested Semester Prerequisites list with status passed/not passed Co-reqs Button View Resources
Checkbox immediate UI + IndexedDB StudentPassedCourseLocal pending + POST /api/v1/curriculum/passed {course_id, passed, entry_year} idempotency MySQL, OR merge once true stays true unless explicit uncheck confirmation modal "آیا مطمئنید این درس را پاس نکرده‌اید؟", On success server returns updated stats progress bar updates

## Data Fetching
GET /api/v1/curriculum-charts?department_id=&entry_year=&status=approved Workbox cache 1h, GET /api/v1/curriculum/passed?entry_year= list passed, WebSocket? No, polling for curriculum_chart_updated event banner "چارت به‌روزرسانی شد - رفرش کنید"

## Offline V9
Tree cached Workbox CacheFirst 1h, checkbox local immediate IndexedDB sync queue

## API Laravel
GET /api/v1/curriculum-charts, GET /api/v1/curriculum-charts/{id}, POST /api/v1/curriculum-charts, PATCH, POST submit, POST approve, POST reject, GET /api/v1/curriculum/passed, POST /api/v1/curriculum/passed

## Notifications
Submit to Head, approve/reject to Expert, new approved to students dept entry year low polling + Pushe
