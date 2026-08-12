# P09 Student Curriculum Charts - V9 Shared Host

## Route /curriculum
Purpose Select major tree-view separated by entry year checkbox for passed courses with cloud sync IndexedDB + MySQL, prereq popups

## Layout
Top Filters Department dropdown auto own dept student cannot change dept? Allow but default own, Entry Year dropdown list years where approved charts exist 1400 1401 1402 sorted desc, Progress Bar total credits passed/required percentage "85 / 140 واحد پاس شده - 60%", Main Tree expandable by semester/year Each semester node header "ترم 1 - 20 واحد" collapsible Inside List courses leaves Row with Checkbox "پاس شده" checked if StudentPassedCourse passed true Course Code Name Credits Is Required badge "الزامی"/"اختیاری" Prereq icon if has prerequisites, Click row not checkbox opens Course Detail Modal

## Course Detail Modal
Title Course Name Info Code Credits Is Required Suggested Semester Prerequisites list each prereq course code+name status badge "پاس شده" green check if passed else red "پاس نشده" Co-requisites similar Button "مشاهده منابع" navigates resources filtered by course

## Checkbox Flow V9 Shared Host
Click checkbox immediate UI toggle + IndexedDB StudentPassedCourseLocal pending + POST /api/v1/curriculum/passed {course_id, passed, entry_year} idempotency key MySQL IdempotencyKeys, OR merge once true stays true unless explicit uncheck confirmation modal "آیا مطمئنید این درس را پاس نکرده‌اید؟", On success server returns updated stats progress bar updates

## Data Fetching V9
GET /api/v1/curriculum-charts?department_id=&entry_year=&status=approved Workbox cache 1h, GET /api/v1/curriculum/passed?entry_year= list passed course_ids, Polling for curriculum_chart_updated event banner "چارت به‌روزرسانی شد - رفرش کنید"

## States
Loading skeleton tree, Empty No chart for selected entry year illustration "چارت برای ورودی {year} منتشر نشده", Offline cached tree + checkboxes IndexedDB

## Edge V9
Entry year no approved chart empty state, Course in chart not in StudentPassedCourse yet unchecked default, Prereq cycle not possible because expert validation prevents cycle but if exists warning, Student changes entry year filter progress recalculates for that chart StudentPassedCourse entry_year field stores which entry year checkbox belongs to progress per entry year, Many courses 100+ virtualized list per semester

## API Laravel V9
GET /api/v1/curriculum-charts?dept=&entry_year=&status=approved
GET /api/v1/curriculum/passed?entry_year=
POST /api/v1/curriculum/passed
