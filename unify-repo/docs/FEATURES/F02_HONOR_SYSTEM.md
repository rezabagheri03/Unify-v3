# F02 Honor System - V9 Shared Host (Kept)

## Definition
User.academic_status_declared ENUM(normal, conditional, gpa_a, final_semester), is_honor_system_acknowledged BOOL, last_declared_at DATETIME, declaration_count INT, stored MySQL

## UI Flow
Radio 4 with credit limits Normal 12-20, Conditional max14, GPA_A max24, Final max24 + ignore time/exam, acknowledge checkbox required, Button "ثبت وضعیت" POST /api/v1/users/me/academic-status {status, acknowledged=true}, stores count++, logs AuditLog honor_status_change IP, banner current status "وضعیت فعلی: ترم آخر (خوداظهاری)"

## Validation (Warn not block, but credit max enforced)
Client+server validate credit limits, time overlap check day_of_week + time intervals, exam overlap same day 2h buffer, prereq check StudentPassedCourse warn popup "پیش‌نیاز X پاس نشده ادامه می‌دهید؟", coreq allow warning. For final_semester, time/exam checks skipped, max 24 enforced even honor (cannot exceed 24)

## Abuse Detection
Count tracking, flag if final_semester >2 distinct semesters -> set flag final_semester_abuse_flag true, AuditLog is_suspicious true, notify Expert dept via polling + Pushe PHP curl "دانشجو X برای بار سوم ترم آخر", banner yellow, advisor dashboard list flagged, HonorFlag table {id, student_id, flag_type, count, last_declared, resolved bool}

## API
POST /api/v1/users/me/academic-status, GET /api/v1/users/me/academic-status, GET /api/v1/admin/honor-flags, POST /api/v1/admin/honor-flags/{id}/resolve

## Offline
Declaration requires online, shows "برای ثبت وضعیت نیاز به اینترنت است" if offline

## Edge
Changing status after finalization does NOT retroactively invalidate finalized enrollments, but shows warning banner + logs, never declared -> cannot finalize, temp allowed
