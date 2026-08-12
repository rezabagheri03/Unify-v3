# ROLE: SYSTEM ADMIN - V9 Shared Host (Laravel + MySQL + Cloud Host)

## Identity
- ID Personnel ID, role admin, scope university-wide

## Permissions University-Wide
- CAN: Set current semester + global_state enrolling/active/exam with confirmation + AuditLog, university-wide user management view all search Ban/Unban reason/expiry, answer escalated tickets 48h+, close any, upload university-level forms is_university_level=true, manage academic calendar univ + dept, university-wide targeted messaging max 100 per group, final resource approval final authority, professor resource management upload new versions + hard delete former professors notes with audit + scheduled hard delete via cron, upload logo branding, base system config, final note approval, analytics limited no PII, Excel import/export univ-wide, hard delete request approval
- CANNOT: Bulk user import (Owner), password reset (Owner), view full audit logs (Owner read), full read-only super admin limited

## Dashboard
- Global State Panel: Current Semester name, Global State dropdown enrolling/active/exam, Grace Period Ends At countdown if active, Buttons Define New Semester, Switch Phase
- Stats Row: Total Users, Active Today, Open Tickets, Escalated Count, Pending Grace Countdown, Pending Resources Final Approval Count, Storage Used / 10GB limit
- Lists: Recent Bans, Recent Hard Delete Requests, Recent Semester Changes

## Semester & Global State Management
- Define New Semester Form: Name e.g., 1403-2, Start Shamsi, End Shamsi, Is Current default true, On submit POST /api/v1/admin/semesters with confirmation typing semester name, validation no active grace, name unique, triggers soft hide old specs is_active false, enrollments finalized->archived, temp hard deleted, resources untouched, notification via polling + Pushe to all
- Switch Phase Form: Radio enrolling/active/exam, confirmation typing semester name warning enrolling->active starts 24h grace, POST /api/v1/admin/semesters/{id}/global-state {global_state}
- Semester List Table: All semesters name start/end is_current badge global_state actions Set Current Edit Dates View Specs/Enrollments

## User Management
- Search ID/name/role/dept/banned, Filters Role multi Dept multi Banned toggle Academic Status, Table ID Name Role badge Dept Academic Status with honor flag Last Login Banned badge Actions View/Ban/Unban Export Excel Pagination 50
- Detail: ID Name Role Dept Academic Status Declared count last declared Created Last Login Banned status reason Supplementary Details Mobile/Email hidden per privacy (even Admin cannot see mobile/email unless student included in supplementary), Enrollments current+archived, Resources Uploaded, Tickets Created, Audit Logs Recent
- Ban: Modal reason required max 500, Expiry date Shamsi optional permanent else date, confirmation typing user ID, POST /api/v1/admin/users/{id}/ban {reason, expires_at_shamsi}, sets is_banned=1, banned_reason, banned_at now, banned_by self, revokes tokens (delete sanctum tokens), AuditLog ban, notification via polling + Pushe "حساب شما بن شد: {reason}"
- Unban: Button Unban confirmation sets is_banned=0 clears reason AuditLog notification "رفع بن شد"

## Tickets Escalated
- Filters Escalated true Level 1 Admin Level 2 Owner Assigned to me/unassigned Dept Status Search, Table same Expert but univ-wide
- Detail same Expert detail but reassign to Expert dept or self, close any, reply file, escalation history

## Base System Management
- Logo Upload: File PNG/SVG max 2MB sanitized remove script tags via bleach, preview, stores to `/uploads/branding/logo.png`, updates SystemConfig logo_path, invalidates cache, AuditLog, polling to all clients reload logo via ?v=timestamp
- Academic Calendar Management: CRUD univ + dept events, Title Description Start/End Shamsi Event Type is_university_wide dept color_code, creates cron jobs for 7-day and 24h warnings via Laravel Scheduler daily
- University Forms: CRUD where is_university_level=1

## University-wide Targeted Messaging
- Same Expert but no dept restriction max 100 IDs per group, can message banned (Admin can message banned per matrix), search shows name banned badge "بن شده - ادمین می‌تواند پیام دهد", Compose subject body send, rate limit 20/min via MySQL

## Final Note Approval & Hard Delete
- Queue pending + expert_approved needing final, Approve admin_approved badge, Reject reason, Hard Delete former professor notes button Hard Delete confirmation typing title + reason DELETE /api/v1/admin/resources/{id}/hard-delete hard deletes file content immediately + row hard deleted? Keep audit tombstone, AuditLog deletion

## Analytics Limited
- No PII, charts active users today/week, downloads per dept bar, ticket response time avg, honor abuse flags count, intranet mode usage polling vs Pushe, storage used / 10GB limit
- Filters Semester Dept Date

## Offline V9
- Admin actions require online except view cached

END ADMIN V9
