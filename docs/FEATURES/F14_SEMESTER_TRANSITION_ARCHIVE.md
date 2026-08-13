# F14 Semester Transition & Archive - V9 Shared Host (MySQL Soft Hide)

## Concepts
No separate archive DB, Soft hide: Old specs is_active=0, enrollments finalized->archived, temp hard deleted, resources untouched evergreen, Archive dropdown UI to view past semesters, same as V7 but MySQL

## Data Model MySQL
Semester id VARCHAR(32) PK name, is_current TINYINT(1) INDEX only one true via unique partial, global_state ENUM enrolling active exam, start_date_g DATETIME end_date_g DATETIME shamsi_original_start/end VARCHAR(10) grace_period_ends_at DATETIME nullable grace_period_handled BOOL default 0
CourseSpecification semester_id FK is_active BOOL
Enrollment semester_id FK status temporary/finalized/archived

## Flow Admin Define New Semester
Button "تعریف ترم جدید" Form Name e.g., 1403-2 Start Shamsi End Shamsi Is Current default true
On submit POST /api/v1/admin/semesters {name, start_shamsi, end_shamsi, is_current}
Server transaction Laravel DB::transaction:
1. Validate no active grace period ongoing grace_period_ends_at > now -> block 400 "مهلت 24 ساعته فعال است"
2. If is_current true set all other semesters is_current false
3. Old current id = previous is_current
4. Old specs is_active false where semester_id=old
5. Old enrollments finalized->archived where semester_id=old status=finalized, temp hard deleted where status=temporary
6. Resources NOT touched evergreen
7. Create new semester row is_current true global_state enrolling default
8. AuditLog major_edit old/new
9. Notification to all via polling + Pushe "ترم جدید {new name} فعال شد - سیستم تابع ترم جدید است"

## Archive Dropdown UI Student
Top Dashboard Dropdown "ترم جاری" default + past semesters where student has archived enrollments SELECT DISTINCT semester_id FROM enrollments WHERE student_id=self and status=archived
On selecting past semester Dashboard loads read-only timetable and course cards for that archived semester GET /api/v1/enrollments?semester=old&status=archived + specs where semester=old even is_active=0 but show because archived view
Course cards same footer actions Download Resources still works evergreen Details modal exam dates old spec Archive view banner "حالت آرشیو - فقط خواندنی" gray overlay

## Archive for Professor/Expert/Admin
Professor dashboard archive toggle view past semesters specs read-only, Expert view past specs own dept read-only, Admin any semester

## Grace Period Interaction
Grace only exists when enrolling->active within same semester, not across semesters, defining new semester while grace active blocked

## API Laravel
GET /api/v1/semesters (list all current first)
GET /api/v1/semesters/current
POST /api/v1/admin/semesters (Admin create)
PATCH /api/v1/admin/semesters/{id}/set-current
PATCH /api/v1/admin/semesters/{id}/global-state {global_state}
GET /api/v1/enrollments?semester={id}&status=
GET /api/v1/specifications?semester={id}&is_active=

## Notifications V9 Polling + Pushe
On new semester push to all, On soft hide old specs no individual push just global

## Edge
Name duplicate 409, grace active block, Student no archived enrollments only current option, Old spec is_active 0 but direct URL allow if archived enrollment exists else 403, Resource evergreen remains accessible

## Audit
Semester creation set-current global-state change logged major_edit old/new
