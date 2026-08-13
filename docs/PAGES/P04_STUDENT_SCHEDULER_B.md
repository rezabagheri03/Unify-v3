# P04 Student Scheduler Phase B Weekly Timetable - V9 Shared Host

## Route /scheduler/active
Access When global_state active OR enrolling but already finalized After finalization in enrolling show active view even if global_state still enrolling banner "ثبت‌نام شما نهایی شده - منتظر شروع ترم" If exam redirect to exam but allow toggle back

### Layout
Top Week navigation Sat-Wed only optional Thu/Fri toggle if university has Thu classes, Main Graphical weekly timetable grid Y time 8-18 half-hour slots X days Sat Sun Mon Tue Wed Thu Fri Thu Fri maybe hidden default, Specs placed blocks day/time height proportional duration 1.5h block, Each block Course Name short Professor short Location small color header deterministic hash professor_id white text, Click block Details modal same Dashboard, Read-only no drag-drop, Bottom Button "مشاهده برنامه امتحانات" if exam period active or global_state exam navigates to /scheduler/exam

### Data Fetching V9
GET /api/v1/enrollments?semester=current&status=finalized specs include day_of_week time_start_end location course name professor name, Cached Workbox runtime cache for offline

### Timetable Logic
Convert time_start/end to minutes calculate top position (start-8*60)/(10*60)*gridHeight, Height (end-start)/(10*60)*gridHeight, Overlap handling if two specs same day overlapping time shouldn't happen unless final_semester show side by side 50% width + red border conflict warning

### States
Loading skeleton grid, Empty if no finalized -> illustration "برنامه خالی است" + button go to enrollment if still enrolling and grace active else "با آموزش تماس بگیرید"

### Offline V9
Cached timetable viewable fully offline via Workbox

### Edge V9
Overnight class time_end < time_start? Assume end>start for V9 overnight not supported weekly view show warning if overnight, Spec change during active via polling 15s later updates timetable + critical banner if conflict

## Notifications V9 Polling
If spec change during active phase timetable updates real-time via polling 15s and shows critical banner if conflict
