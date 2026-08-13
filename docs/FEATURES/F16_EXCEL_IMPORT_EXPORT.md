# F16 Excel Import/Export - V9 Shared Host (PhpSpreadsheet)

## Purpose
Managerial roles upload Excel following standard format displayed in panel and export data to Excel based on access level, transactional

## Encoding
Server generates Excel with PhpSpreadsheet, UTF-8, Persian headers row1, English id headers row2 hidden for parsing robustness, data rows row3, Client uses SheetJS xlsx library, validates UTF-8 without BOM but handles BOM if present strip

## Templates Detailed

### Users Import (Owner)
Columns Student ID/Personnel ID (A), First Name (B), Last Name (C), Role (D) enum student/professor/expert/head_of_dept/admin/owner Persian mapping "دانشجو"->student etc English, Department ID (E) required staff, Academic Status (F) enum normal/conditional/gpa_a/final_semester Persian "عادی"/"مشروط"/"ممتاز"/"ترم آخر", Validation ID unique within file + DB, Role valid, Dept exists if required, Academic Status valid

### Courses Import (Expert Dept, Admin Univ)
Columns Course Code (A) unique, Course Name (B), Credits (C) 0-6 int, Department ID (D) must be own dept for Expert, Validation Code unique, Credits int range, Dept exists matches scope

### Specifications Import (Expert Dept, Admin Univ)
Columns Course Code (A), Professor Personnel ID (B), Day of Week (C) Persian شنبه/یکشنبه/دوشنبه/سه‌شنبه/چهارشنبه/پنجشنبه/جمعه or English sat/sun/mon/tue/wed/thu/fri, Time Start (D) HH:MM, Time End (E) HH:MM, Location (F), Telegram Link (G) URL https://t.me/, Final Exam Date Shamsi (H) YYYY/MM/DD, Midterm Exam Date Shamsi (I) optional, Semester ID (J) e.g., 1403-1, Validation Course exists own dept Professor exists own dept role professor Day enum Time End>Start Location required Telegram URL valid optional Shamsi dates valid Jalali via Morilog Semester exists

### Curriculum Chart Import (Expert/Head)
Columns Entry Year (A) int 1395-1410, Course Code (B), Is Required (C) بله/خیر Yes/No bool, Prerequisites (D) comma separated codes e.g., "CS101,CS102", Suggested Semester (E) int 1-12, Validation Entry year int Course exists own dept Is Required bool Persian mapping Prerequisites exist own dept not circular Suggested Semester int

### Additional
Academic Calendar Import: Title Description Start Shamsi End Shamsi Event Type Is University Wide بله/خیر Department ID

## Import Flow Transactional V9
1. User uploads Excel via POST /api/v1/import/{type} multipart max 5MB max 2000 rows per file
2. Server Save temp, parse PhpSpreadsheet, validate header row matches expected Persian headers allow reordered? Require exact order for simplicity
3. For each row validate per template rules collect errors array {row_number, column, error_message Persian, raw_value}
4. If any row error: ROLLBACK (DB::transaction rollback, no DB changes), generate error report Excel: Original file with extra column "خطا" containing error message per row highlight error cells red, return error report file download + JSON errors array, no partial import
5. If all valid: BEGIN transaction, insert/update per type with upsert? For Users duplicate ID existing DB error to prevent overwrite, For Courses/Specs duplicate code update existing upsert, For Users duplicate error
6. On success COMMIT return success count AuditLog major_edit with count, for Users generate ZIP envelopes dompdf
7. Push notification? Low priority "دروس جدید وارد شد" via polling + Pushe

## Export Flow
GET /api/v1/export/{type}?department_id=&semester_id= -> queries DB scoped per role, generates Excel same structure as import templates, Shamsi YYYY/MM/DD Credits int Boolean Persian "بله"/"خیر" in data rows but also English hidden row2 for re-import robustness, file name e.g., courses-1403-1-1403-04-20.xlsx, Export respects scope Expert own dept only Admin all Student cannot export

## Validation Details V9
Row limit 2000 to prevent DoS, File size max 5MB, MIME check application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, Shamsi date validation via Morilog\Jalali isValid, Time HH:MM regex, URL Telegram must start https://t.me/, Department ID must exist, Role enum mapping Support Persian "دانشجو"->student etc + English enum, Boolean Persian mapping "بله"/"خیر"/Yes/No/True/False/1/0

## API Laravel
POST /api/v1/import/users (Owner) multipart
POST /api/v1/import/courses (Expert/Admin)
POST /api/v1/import/specifications (Expert/Admin)
POST /api/v1/import/curriculum-charts (Expert/Head/Admin)
POST /api/v1/import/academic-calendar (Expert/Admin)
GET /api/v1/export/users (Owner)
GET /api/v1/export/courses?department_id=&semester_id=
GET /api/v1/export/specifications
GET /api/v1/export/curriculum-charts
GET /api/v1/export/academic-calendar

## Offline V9
Import/Export requires online (file processing server side)

## Edge
Excel with BOM Strip BOM, Persian headers but English id row missing still parse via Persian header mapping, Empty required field error "فیلد {column} الزامی است - ردیف {row}", Duplicate ID within file error "شناسه تکراری در فایل - ردیف {row1} و {row2}", Duplicate ID in DB for Users error "شناسه {id} قبلا وجود دارد", Shamsi invalid 1403/13/40 error "تاریخ شمسی نامعتبر", Time End before Start error "زمان پایان قبل از شروع", File size 10MB 400 "حجم فایل حداکثر 5 مگابایت", Rows 2500 400 "حداکثر 2000 ردیف"
