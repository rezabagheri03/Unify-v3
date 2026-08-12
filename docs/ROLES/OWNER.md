# ROLE: SYSTEM OWNER - V9 Shared Host (Laravel + MySQL)

## Identity
- ID Personnel ID, role owner, scope university-wide, super admin read-only + user management

## Permissions Super
- CAN: Full read-only entire system (can view any page any dept any semester), user/role management manually add via ID+role+dept, bulk user upload via Excel transactional (see F16), password resets search ID and set new temp for in-person IT requests generates sealed envelope PDF dompdf, audit log access view history all sensitive actions, analytics full with PII optional, university-wide targeted messaging, ban/unban, can view but not set semester? Owner can set as well but audit logged, per matrix read-only but we allow set as super
- CANNOT: Hard delete (Admin), approve resources final (Admin final)

## Dashboard Owner
- Stats: Total Users, Active Today/Week/Month, Total Resources, Total Downloads, Open Tickets, Escalated Tickets, Pending Grace, Honor Abuse Flags Count, Failed Login Today, Intranet Mode % polling vs Pushe, Storage Used /10GB, Total Users
- Quick Actions: Manual Add User, Bulk Import, Password Reset Search, Audit Logs, Analytics, Read-Only System View

## Manual Add User
- Form: Student ID/Personnel ID unique live check, First Name, Last Name, Role dropdown student/professor/expert/head/admin/owner, Department dropdown required staff, Academic Status dropdown optional
- On Save POST /api/v1/owner/users {id, first_name, last_name, role, department_id, academic_status}, generates temp 12 chars Str::random(12), hashed Argon2id Hash::make, must_change_password=1, expires 7d, creates user, logs AuditLog creation, returns envelope PDF download (dompdf, QR, instructions Persian)

## Bulk Import
- Template Download Excel Persian headers sample row
- Upload drag-drop Excel max 5MB max 2000 rows, preview first 10 rows
- Validation transactional: ID uniqueness within file + DB, role enum, dept exists, academic status enum
- If errors: Error report Excel download with column خطا red highlight, table errors row+error
- If success: Success count + ZIP download envelopes PDFs per user, table created ID/Name/Role
- Rate limit 1 per 10 min via MySQL
- Flow same as V7 but PHP PhpSpreadsheet

## Password Reset & Envelope (IT Handout Core)
- Search Student/Personnel ID shows profile card ID/Name/Role/Dept Last Reset At/Reset Count
- Reset Button modal Reason required "درخواست حضوری با کارت شناسایی", Checkbox receipt signed optional, Confirm typing user ID
- On Confirm POST /api/v1/owner/users/{id}/reset-password {reason, receipt_signed}, generates new temp 12 chars, invalidates all sanctum tokens (logout everywhere), must_change_password=1, expires 7d, logs AuditLog password_reset is_suspicious if >2 per month, returns envelope PDF download immediate (dompdf, not stored)
- Envelope PDF: University logo, Title "پاکت رمز موقت سامانه Unify", Student Name, ID, Username, Temp Password large monospace, QR code username+temp, Instructions, Printed date, Operator name, Warning 7 days, A5 printable, generated on-fly
- History table recent resets for this user

## Audit Logs Viewer
- Filters: User ID, Action multi deletion/major_edit/password_reset/role_change/ban/honor_status_change/final_semester_abuse_flag/login/failed_login, Resource Type, Resource ID, Timestamp From/To Shamsi pickers, Is Suspicious toggle, IP search
- Table: Timestamp Shamsi+Gregorian sortable, Actor ID/name, Action badge color, Resource Type/ID, IP, User Agent truncated, Details button, Suspicious red badge
- Details Modal: Decrypted details JSON pretty diff old/new, IP, UA, is_suspicious, decrypted via Crypt::decryptString
- Export: Button Excel/CSV filtered requires reason modal + AuditLog export action itself
- Pagination 50

## Analytics Full
- Filters Semester Dept Date Range Role
- KPI Cards: DAU WAU MAU Total Users Resources Downloads Avg Session Ticket Avg Response Assignment Submission Curriculum Checkbox Completion Honor Abuse Flags
- Charts: Active Users Line, Downloads per Dept Bar, Top Resources Table top10, Top Professors, Ticket Response Histogram, Honor Status Pie normal/conditional/gpa_a/final, Intranet Mode Stats % polling vs Pushe, Storage Usage donut per dept, Failed Login line
- Tables: Flagged Students list ID/Name/Flag Type/Count/Last Declared/Action Resolve
- Export Full CSV optional PII requires reason + audit

## Super Read-Only System View
- Banner red "حالت فقط خواندنی مالک سیستم"
- Sidebar all roles pages read-only links: Student Dashboard view as any student ID param, Professor Dashboard view as any professor, etc.
- Student View As Input Student ID -> navigates to /owner/system/read-only/student/{id}/dashboard shows student dashboard read-only disabled actions watermark فقط خواندنی
- No impersonate write, only view, except user management + password reset + audit

## Audit Logs for Owner
- All Owner actions logged too (self audit)

END OWNER V9
