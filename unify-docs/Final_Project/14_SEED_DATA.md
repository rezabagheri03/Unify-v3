# 14 - Seed Data - For 600 CS Students - V9 Shared Host

This doc defines realistic seed data for testing, so LLM can test with 600 students, not empty DB. Provides CSV templates and seeder classes.

## Overview

For 600 CS students start, you need:

- 3 Departments: CS (Computer Engineering), maybe CE, but for 600 we have CS only with id `CS`
- 1 Owner, 2 Admins, 3 Heads (CS), 5 Experts (CS), 20 Professors (CS), 600 Students
- 40 Courses for CS (from entry year 1401 curriculum)
- 100 CourseSpecifications for current semester 1403-1 (each course 2-3 specs different professors/times)
- 10 Resources (PDFs) evergreen for popular courses
- 5 Tickets (sample open/answered/closed/escalated)
- 10 Assignment Trackers sample
- 5 Academic Calendar events (registration_open, registration_close, semester_start, exam_period_start, holiday)
- 10 Forms (3 university-level, 7 dept)
- 20 NoticeBoard + 20 FAQs for specs

## Departments Seed

**File:** `database/seeders/DepartmentSeeder.php`

```php
Department::create(['id'=>'CS', 'name_fa'=>'مهندسی کامپیوتر', 'name_en'=>'Computer Engineering']);
Department::create(['id'=>'CE', 'name_fa'=>'مهندسی برق', 'name_en'=>'Electrical']);
```

## Users Seed - 600 Students + Staff

### Owner (1)
- id: `OWNER001`, role owner, first_name `مدیر`, last_name `سیستم`, department_id null, password_hash Argon2id temp `Owner@123!` must_change_password=1

### Admin (2)
- id: `ADMIN001`, role admin, dept null, name `حسین حسینی`
- id: `ADMIN002`, role admin, name `ادمین دوم`

### Head (3 for CS)
- id: `HEAD_CS01`, role head_of_dept, department_id CS, name `دکتر کریمی`

### Expert (5 for CS)
- id: `EXP_CS01` .. `EXP_CS05`, role expert, department_id CS

### Professors (20 for CS)
- id: `P1001` .. `P1020`, role professor, department_id CS, names Dr. Havand, Dr. Ahmadi, etc.

### Students (600)
- Generate via Excel import template Users Import: Columns Student ID/Personnel ID, First Name, Last Name, Role, Department ID, Academic Status
- Student IDs: `400100001` to `400100600` (600)
- First Names random Persian: سارا, علی, محمد, زهرا, حسین, etc.
- Last Names random: احمدی, کریمی, حسینی, etc.
- Role student, Department CS, Academic Status random: 400 Normal, 50 Conditional, 50 GPA_A, 100 Final Semester (to test honor abuse flag)
- For bulk import, create CSV file `seed_users_600.csv` with 600 rows, then import via Owner dashboard bulk import -> generates ZIP 600 envelopes PDFs

**File:** `seed_users_600.csv` (example 10 rows shown, repeat to 600):

```csv
Student ID,First Name,Last Name,Role,Department ID,Academic Status
400100001,سارا,احمدی,student,CS,normal
400100002,علی,کریمی,student,CS,normal
400100003,محمد,حسینی,student,CS,conditional
400100004,زهرا,موسوی,student,CS,gpa_a
400100005,حسین,رضایی,student,CS,final_semester
400100006,مریم,جعفری,student,CS,normal
400100007,امیر,صادقی,student,CS,normal
400100008,نرگس,اکبری,student,CS,normal
400100009,رضا,محمدی,student,CS,normal
400100010,فاطمه,حسینی,student,CS,normal
... (to 400100600)
```

## Courses Seed - 40 CS Courses

**File:** `database/seeders/CourseSeeder.php`

```php
$courses = [
  ['id'=>'CS101','code'=>'CS101','name'=>'ریاضی 1','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS102','code'=>'CS102','name'=>'ریاضی 2','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS103','code'=>'CS103','name'=>'فیزیک 1','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS104','code'=>'CS104','name'=>'مبانی کامپیوتر','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS105','code'=>'CS105','name'=>'برنامه‌نویسی مقدماتی','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS201','code'=>'CS201','name'=>'ساختمان داده','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS202','code'=>'CS202','name'=>'مدار منطقی','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS203','code'=>'CS203','name'=>'ریاضی گسسته','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS204','code'=>'CS204','name'=>'آمار و احتمال','credits'=>3,'department_id'=>'CS'],
  ['id'=>'CS205','code'=>'CS205','name'=>'معماری کامپیوتر','credits'=>3,'department_id'=>'CS'],
  // ... up to 40
];
```

**CSV Template Courses Import** `seed_courses_40.csv`:

```csv
Course Code,Course Name,Credits,Department ID
CS101,ریاضی 1,3,CS
CS102,ریاضی 2,3,CS
CS103,فیزیک 1,3,CS
...
CS240,شبکه‌های کامپیوتری,3,CS
```

## CourseSpecifications Seed - 100 Specs for Current Semester 1403-1

**File:** `database/seeders/SpecificationSeeder.php`

For each course 2-3 specs different professors times:

```php
// Course CS101 Math 1 - 3 specs
['id'=>'SPEC001','course_id'=>'CS101','professor_id'=>'P1001','day_of_week'=>'sat','time_start'=>'08:00','time_end'=>'10:00','location'=>'کلاس 101','telegram_link'=>'https://t.me/cs101_p1001','exam_date_final_g'=>'2024-07-10 08:00:00','shamsi_original_final'=>'1403/04/20','exam_date_midterm_g'=>'2024-06-10 08:00:00','shamsi_original_midterm'=>'1403/03/21','is_active'=>1,'semester_id'=>'1403-1'],
['id'=>'SPEC002','course_id'=>'CS101','professor_id'=>'P1002','day_of_week'=>'sun','time_start'=>'10:00','time_end'=>'12:00','location'=>'کلاس 102','telegram_link'=>'https://t.me/cs101_p1002','exam_date_final_g'=>'2024-07-10 14:00:00','shamsi_original_final'=>'1403/04/20','exam_date_midterm_g'=>null,'shamsi_original_midterm'=>null,'is_active'=>1,'semester_id'=>'1403-1'],
// ... up to 100 specs
```

**CSV Template Specifications Import** `seed_specifications_100.csv`:

```csv
Course Code,Professor Personnel ID,Day of Week,Time Start,Time End,Location,Telegram Link,Final Exam Date Shamsi,Midterm Exam Date Shamsi,Semester ID
CS101,P1001,شنبه,08:00,10:00,کلاس 101,https://t.me/cs101_p1001,1403/04/20,1403/03/21,1403-1
CS101,P1002,یکشنبه,10:00,12:00,کلاس 102,https://t.me/cs101_p1002,1403/04/20,,1403-1
...
```

## StudentPassedCourses Seed - For Curriculum & Prereq Testing

For 600 students, random passed courses:

- For student 400100001 (normal): Passed 20 courses entry year 1401, e.g., CS101, CS103, etc. with grade 15-20
- For student 400100003 conditional: Passed only 10 courses low grades 10-12
- Use seeder loop random

```php
// For each student, random 10-30 passed courses
foreach (User::where('role','student')->get() as $student) {
  $courses = Course::inRandomOrder()->limit(rand(10,30))->get();
  foreach ($courses as $course) {
    StudentPassedCourse::create([
      'id'=>Str::uuid(),
      'student_id'=>$student->id,
      'course_id'=>$course->id,
      'passed'=>true,
      'grade'=>rand(100,200)/10, // 10.0-20.0
      'entry_year'=>1401,
    ]);
  }
}
```

## Resources Seed - 10 Sample PDFs Evergreen

Create 10 fake PDF files (small 100KB) in `/storage/app/public/resources/CS/P1001/` for testing:

- Title: "جزوه ریاضی 2 - دکتر هاوند - فصل 1", course_id CS102, professor_id P1001, uploader_id P1001, file_path `/uploads/resources/CS/P1001/uuid.pdf`, file_size 104857, file_mime application/pdf, status approved, version 1, family_id same as id, badge_type professor, average_rating 4.5, rating_count 10, download_count 50

Seeder creates Resource rows + copies dummy PDFs to storage.

## Tickets Seed - 5 Sample

- Open: student 400100001, dept education, subject "مشکل در انتخاب واحد", description "تداخل زمانی دارم", status open
- In Progress: student 400100002, assigned_to EXP_CS01, status in_progress
- Answered: student 400100003, assigned_to EXP_CS01, status answered, with 1 staff reply
- Closed: student 400100004, status closed, closed_at now, with 2 replies
- Escalated: student 400100005, status open, is_escalated true, escalated_at now-49h, escalation_level 1 (should be escalated to admin)

## Assignment Trackers Seed - 10 Sample

For student 400100001, 2 assignments per spec enrolled:

- Title "تمرین 1 ساختمان داده", spec SPEC for CS201, due_date_g now+7 days, shamsi_original 1403/04/27, reminder 24h, status pending
- Title "پروژه شبکه", spec CS240, due_date_g now-2 days (overdue), status late, etc.

## Academic Calendar Seed - 5 Events

- registration_open: title "شروع ثبت‌نام", start 1403/06/01, end 1403/06/01, event_type registration_open, is_university_wide true
- registration_close: 1403/06/10, registration_close
- semester_start: 1403/07/01, semester_start
- exam_period_start: 1403/10/10, exam_period_start
- holiday: 1403/06/15, holiday, title "تعطیل رسمی"

## Forms Seed - 10 Forms

- 3 univ level: is_university_level true, title "فرم مرخصی تحصیلی", file /uploads/forms/univ/uuid.pdf, signature_guide "امضا مدیر گروه + مهر آموزش + امضا معاون آموزشی"
- 7 dept: department_id CS, is_university_level false, title "فرم حذف اضطراری", etc.

## NoticeBoard + FAQ Seed - 20 each

For spec SPEC001, create 2 notices: priority high "میان‌ترم هفته آینده" expires 1403/04/30, priority low "کلاس جبرانی شنبه"
For spec SPEC001, create 2 FAQs: Q "منابع امتحان چیست؟" A "فصل 1 تا 5 کتاب", is_pinned true

## Semester Seed

- id 1403-1, name "1403 - ترم 1", is_current true, global_state enrolling, start 1403/07/01, end 1403/11/30, grace_period_ends_at null, handled false
- id 1402-2, name "1402 - ترم 2", is_current false, global_state exam, start 1402/11/01, end 1403/03/31, archived for testing archive dropdown

## SystemConfig Seed

- key brand_name value Unify
- key logo_path value /uploads/branding/logo.png
- key default_theme_id value unify-blue

## How to Seed

```bash
# On local dev
php artisan migrate:fresh --seed
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=CourseSeeder
php artisan db:seed --class=SemesterSeeder
php artisan db:seed --class=SpecificationSeeder
php artisan db:seed --class=UserSeeder600 # creates 600 students via factory + courses passed
php artisan db:seed --class=ResourceSeeder
php artisan db:seed --class=TicketSeeder
php artisan db:seed --class=AssignmentSeeder
php artisan db:seed --class=CalendarSeeder
php artisan db:seed --class=FormSeeder
php artisan db:seed --class=NoticeFaqSeeder
php artisan db:seed --class=SystemConfigSeeder

# Or single
php artisan db:seed
```

## CSV Files Provided

In `Final_Project/SEED_DATA/` folder, provide 4 CSVs:

- `seed_users_600.csv` (600 rows, 6 columns)
- `seed_courses_40.csv` (40 rows)
- `seed_specifications_100.csv` (100 rows)
- `seed_curriculum_1401.csv` (entry year 1401, 40 rows)

These CSVs can be directly imported via Owner dashboard bulk import Excel (convert CSV to XLSX) or via `php artisan import:users seed_users_600.csv`

## For 600 Students Testing

- Use seeder to create 600 students, 40 courses, 100 specs, 10 resources, 5 tickets, etc.
- Login as student 400100001 with temp password from seeder (e.g., Student@123! must_change_password true) to test onboarding + honor + enrollment + resource download + rating + ticket + assignment + polling notifications
- Login as professor P1001 to test upload resource auto-approved + student list + broadcast
- Login as expert EXP_CS01 to test course/spec CRUD + change alert + pending approval + targeted messaging
- Login as admin ADMIN001 to test define new semester + switch phase enrolling->active starts grace 24h + ban user + escalated tickets
- Login as owner OWNER001 to test bulk import + password reset envelope ZIP + audit logs

END SEED DATA
