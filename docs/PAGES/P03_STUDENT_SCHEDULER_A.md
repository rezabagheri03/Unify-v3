# P03 Student Scheduler Phase A (Enrollment) - V9 Shared Host (Laravel + Polling)

## Route /scheduler/enrolling
Access Only when global_state=enrolling OR grace active (for finalization only) If active and grace not active redirect /scheduler/active If exam redirect /scheduler/exam

### Layout
Top Honor System Section + Credit Summary + Grace Countdown if active, Middle Search & Filter + Available Specs List + Temporary List Sidebar/Bottom Sheet, Bottom Final Submit Button

### Honor System Section
Radio 4 options with credit limits, Acknowledge checkbox required, Button "ثبت وضعیت" POST /api/v1/users/me/academic-status, After declared banner current status + ability to change status during Phase A before finalization count++ logs

### Credit Summary
Current temporary credits sum min/max per declared status progress bar "18 / 12-20 واحد" green if within red if over/under, Warnings Time conflicts Exam conflicts Prereq warnings

### Search & Filter
Search bar Name/Code debounce 300ms, Filters Department Credits Day multi-select sat-wed-thu Time range slider 8-18 Professor search, Sort Name Credits Time

### Available Specs List
Virtualized list 20 per page infinite scroll, Each spec card Course Name Code Professor Day Time Location Credits Exam date Add button "افزودن" primary if not in temp else "حذف" secondary, Add logic checks time overlap day_of_week exam overlap prereq warning popup credit limit warning if passes adds to temp list, Time overlap check includes day_of_week if overlap and status != final_semester error snackbar "تداخل زمانی با {course}" block add, If final_semester and overlap warning "تداخل نادیده گرفته شد (ترم آخر)" allow add, Prereq check StudentPassedCourse not passed modal warning "پیش‌نیاز {course} پاس نشده ادامه می‌دهید؟" Continue/Cancel, Coreq allow

### Temporary List
Sidebar desktop bottom sheet mobile List added specs temp total credits each row Course Name Day/Time Delete icon, Shows conflict warnings if any (if spec changed after add conflict may appear via polling), Button "پاک کردن همه" confirmation

### Golden Schedule Button Modal
Button "پیشنهاد برنامه طلایی" icon lightbulb On click modal preferences free days checkboxes max gap slider prefer professors multi-select prefer morning toggle Generate button GET /api/v1/golden-schedule with prefs loading list 15 suggestion cards score explanation Apply bulk adds to temp after checking conflicts

### Final Submit
Button "نهایی‌سازی" bottom sticky disabled if credit violation honor not declared empty, Confirmation modal "آیا از نهایی‌سازی {credits} واحد مطمئنید؟", POST /api/v1/enrollment/final idempotency key MySQL IdempotencyKeys table, On success snackbar "ثبت‌نام نهایی شد" navigates to /scheduler/active, Offline Final requires online "برای نهایی‌سازی نیاز به اینترنت است" if offline

### Data Fetching V9
GET /api/v1/specifications?semester=current&search=&day=&page=, GET /api/v1/enrollment/temp, POST temp {spec_id}, DELETE temp/{id}, GET academic-status, GET golden-schedule, Polling spec_updated event if spec in temp list time changed show critical banner mark conflict red

### States V9
Loading skeleton, Empty search no results, Grace active Add disabled only Final enabled banner countdown, Offline Add/remove queued IndexedDB final requires online golden offline Web Worker cached data

### Edge V9
Add spec just deleted by Expert 404 on POST temp "این مشخصه حذف شده" refresh list, Credit exactly boundary allow 20 allowed 21 blocked, Prereq warning Continue adds with warning badge, Temporary list 10 specs credit 20 add 11th 3 credits exceed max 20 block "سقف واحد"
