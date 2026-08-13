# P17 System Owner Pages - V9 Shared Host (Laravel + MySQL)

## Routes
/owner dashboard, /owner/users/manual-add, /owner/users/bulk-import, /owner/users/{id}/reset-password, /owner/audit-logs, /owner/analytics/full, /owner/system/read-only, /owner/settings

## Dashboard /owner
Stats Total Users Active Today/Week/Month Total Resources Downloads Open Tickets Escalated Pending Grace Honor Abuse Flags Failed Login Today Intranet Mode % polling vs Pushe Storage Used /10GB, Quick Actions Manual Add Bulk Import Password Reset Search Audit Logs Analytics Read-Only Recent Activity Recent User Creations Resets Bans Semester Changes

## Manual Add User /owner/users/manual-add
Form Student ID/Personnel ID unique live First Last Role dropdown student/professor/expert/head/admin/owner Department dropdown required staff Academic Status dropdown optional, On Save POST /api/v1/owner/users {id, first_name, last_name, role, department_id, academic_status} generates temp 12 chars Str::random hashed Argon2id Hash::make must_change_password=1 expires 7d creates user logs AuditLog returns envelope PDF download dompdf

## Bulk Import /owner/users/bulk-import
Template Download Excel Persian headers sample row, Upload drag-drop Excel max5MB max2000 rows preview first10 Validation transactional ID uniqueness within file + DB role enum dept exists academic status enum If errors Error report Excel download column خطا red highlight table errors row+error If success Success count + ZIP download envelopes PDFs per user table created ID/Name/Role Rate limit 1 per 10 min

## Password Reset Envelope /owner/users/{id}/reset-password
Search ID shows profile card ID/Name/Role/Dept Last Reset At/Count Reset Button modal Reason required "درخواست حضوری با کارت شناسایی" Checkbox receipt signed optional Confirm typing user ID POST /api/v1/owner/users/{id}/reset-password {reason, receipt_signed} generates new temp 12 chars invalidates all sanctum tokens must_change_password=1 expires 7d logs AuditLog is_suspicious if >2 per month returns envelope PDF download immediate dompdf not stored, History table recent resets for user, Envelope PDF University logo Title "پاکت رمز موقت سامانه Unify" Name ID Username Temp Password large monospace QR username+temp Instructions date operator warning 7 days A5

## Audit Logs Viewer /owner/audit-logs
Filters User ID Action multi deletion/major_edit/password_reset/role_change/ban/honor_status_change/final_semester_abuse_flag/login/failed_login Resource Type Resource ID Timestamp From/To Shamsi pickers Is Suspicious toggle IP search, Table Timestamp Shamsi+Gregorian sortable Actor ID/name Action badge color Resource Type/ID IP User Agent truncated Details button Suspicious red badge, Details Modal Decrypted details JSON pretty diff old/new via Crypt::decryptString, Export Excel/CSV filtered requires reason modal AuditLog export action itself, Pagination 50

## Analytics Full /owner/analytics/full
Filters Semester Dept Date Range Role, KPI Cards DAU WAU MAU Total Users Resources Downloads Avg Session Ticket Avg Response Assignment Submission Curriculum Checkbox Completion Honor Abuse Flags, Charts Active Users Line Downloads per Dept Bar Top Resources Table top10 Top Professors Ticket Response Histogram Honor Pie Intranet Mode Stats % polling vs Pushe Storage Usage donut per dept Failed Login line Tables Flagged Students list ID/Name/Flag Type/Count/Last Declared/Action Resolve Export Full CSV optional PII requires reason audit

## Super Read-Only System View /owner/system/read-only
Banner red "حالت فقط خواندنی مالک سیستم", Sidebar all roles pages read-only links Student Dashboard view as any student ID param Professor Dashboard view as any professor etc, Student View As Input Student ID navigates /owner/system/read-only/student/{id}/dashboard shows student dashboard read-only disabled actions watermark فقط خواندنی, No impersonate write only view except user management + password reset + audit

## Settings /owner/settings Same plus SystemConfig viewer current logo path brand name

## Edge V9 Shared Host
Bulk 600 ZIP 100MB streaming, Reset banned allowed, Audit export large streaming, Read-only view as no data empty states
