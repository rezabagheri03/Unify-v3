# P16 System Admin Pages - V9 Shared Host (Laravel + MySQL + Cloud Host 10GB Shop)

## Routes
/admin dashboard, /admin/semesters global state, /admin/users search ban/unban, /admin/users/{id}, /admin/tickets/escalated, /admin/tickets/{id}, /admin/branding/logo, /admin/forms/university, /admin/calendar/university, /admin/resources/final-approval, /admin/messaging/university, /admin/analytics/limited, /admin/excel/import-export, /admin/settings

## Dashboard /admin
Global State Panel Current Semester name Global State dropdown enrolling/active/exam Grace Period Ends countdown if active Buttons Define New Semester Switch Phase, Stats Total Users Active Today Open Tickets Escalated Count Pending Grace Countdown Pending Resources Final Approval Count Storage Used / 10GB limit, Lists Recent Bans Recent Hard Delete Requests Recent Semester Changes

## Semester & Global State Management /admin/semesters
Current Semester Card Name Start/End Shamsi Global State badge Grace Ends countdown History, Define New Semester Form Name e.g., 1403-2 Start Shamsi End Shamsi Is Current default true On submit POST /api/v1/admin/semesters with confirmation typing name Validation no active grace name unique Triggers soft hide old specs is_active 0 enrollments finalized->archived temp hard deleted resources untouched notification via polling + Pushe to all, Switch Phase Form Radio enrolling/active/exam confirmation typing semester name warning enrolling->active starts 24h, Semester List Table All semesters name start/end is_current badge global_state actions Set Current Edit Dates View Specs/Enrollments

## User Management /admin/users
Search ID/name/role/dept/banned Filters Role multi Dept multi Banned toggle Academic Status Sort Table ID Name Role badge Dept Academic Status with honor flag Last Login Banned badge Actions View/Ban/Unban Export Excel Pagination 50, Detail ID Name Role Dept Academic Status Declared count last declared Created Last Login Banned status reason Supplementary Details Mobile/Email hidden per privacy even Admin cannot see mobile/email unless student included in supplementary, Enrollments current+archived Resources Uploaded Tickets Created Audit Logs Recent, Ban Modal reason required max500 Expiry date Shamsi optional permanent else date confirmation typing user ID POST /api/v1/admin/users/{id}/ban {reason, expires_at_shamsi} sets is_banned=1 banned_reason banned_at now banned_by self revokes tokens AuditLog notification polling + Pushe "حساب شما بن شد: {reason}" Unban button

## Tickets Escalated /admin/tickets/escalated
Filters Escalated true Level 1 Admin Level 2 Owner Assigned to me/unassigned Dept Status Search Table same Expert but univ-wide, Detail same Expert detail but reassign to Expert dept or self close any reply file escalation history

## Branding Logo /admin/branding/logo
Current Logo preview File info size dimensions Upload drag-drop PNG/SVG max2MB preview new Save Brand Name field max50 default Unify POST /api/v1/admin/branding/logo multipart file + brand_name sanitized SVG stores /uploads/branding/logo.png SystemConfig logo_path AuditLog polling to all clients reload logo ?v=timestamp

## Forms University /admin/forms/university
List Table Title Description File size Guide Is Univ true Is Active Actions Edit/Delete/Download Tabs University + Dept All read-only Add/Edit same Expert but is_university_level true checkbox

## Calendar University /admin/calendar/university
List Table Title Desc Start/End Shamsi Event Type badge Is Univ true Dept Actions Tabs Univ + All Depts Add/Edit same Expert but univ true option dept dropdown if not univ

## Resources Final Approval /admin/resources/final-approval
List pending + expert_approved needing final, Actions Approve admin_approved badge Reject reason Hard Delete former professor notes button Hard Delete confirmation typing title + reason DELETE /api/v1/admin/resources/{id}/hard-delete hard deletes file content /uploads immediately AuditLog, LRU cleanup cron daily to keep under 10GB Shop limit

## University-wide Targeted Messaging /admin/messaging/university
Same Expert but no dept restriction max 100 IDs per group can message banned Admin can message banned, Compose subject body send rate limit 20/min

## Analytics Limited /admin/analytics/limited
No PII charts Active users today/week Downloads per dept bar Ticket response time avg Honor abuse flags count Intranet mode usage polling vs Pushe Storage Used /10GB limit Filters Semester Dept Date Export limited CSV no PII

## Excel Import/Export /admin/excel Same Expert but univ scope

## Settings /admin/settings Same

## Offline V9
Admin actions require online except view cached Workbox
