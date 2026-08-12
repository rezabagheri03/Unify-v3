# P00 Navigation Sitemap - V9 Shared Host

## Student Sitemap
- /login (P01)
- /onboarding (P01)
- / (Dashboard P02) default after login, /archive?semester=..., /course/{specId}/details modal
- /scheduler /scheduler/enrolling (Phase A P03) /scheduler/enrolling/golden modal, /scheduler/active (Phase B P04) weekly timetable, /scheduler/exam (Phase C P05) flip
- /resources (P06) /resources?course=&prof= filtered, /resources/{id} detail, /resources/upload student upload, /resources/my-uploads
- /inbox (P07) /inbox/{messageId} thread, /inbox/compose?recipient= private reply
- /tickets (P08) /tickets/{id} detail reply, /tickets/new
- /curriculum (P09) /curriculum?dept=&entryYear=
- /forms (P10), /calendar (P10) /calendar/{id}, /notices/{specId}, /faq/{specId}
- /assignments (P11) /assignments/{id}, /assignments/new
- /settings (P12) /settings/theme, /settings/notifications mute per spec, /settings/profile, /settings/password, /settings/offline-queue, /settings/intranet-status

## Professor Sitemap
- /login, /professor dashboard P13, /professor/specs/{specId}/students, /professor/resources own + pending, /professor/resources/{id}, /professor/resources/upload, /professor/messages, /professor/noticeboard/{specId}, /professor/faq/{specId}, /professor/settings

## Expert Sitemap
- /expert dashboard P14, /expert/courses CRUD, /expert/courses/{id}/edit, /expert/courses/new, /expert/specifications CRUD, /expert/specifications/{id}/edit, /expert/specifications/new, /expert/specifications/import Excel, /expert/prereq, /expert/curriculum list, /expert/curriculum/{entryYear} editor, /expert/forms dept, /expert/tickets help desk, /expert/tickets/{id}, /expert/messaging/targeted, /expert/resources/pending, /expert/excel/import-export, /expert/settings

## Head Sitemap inherits Expert + 
- /head dashboard P15, /head/curriculum/pending-approval final approval, /head/oversight/professors

## Admin Sitemap
- /admin dashboard P16, /admin/semesters global state define new, /admin/users search ban/unban, /admin/users/{id}, /admin/tickets/escalated, /admin/tickets/{id}, /admin/branding/logo, /admin/forms/university, /admin/calendar/university, /admin/resources/final-approval, /admin/messaging/university, /admin/analytics/limited, /admin/excel/import-export, /admin/settings

## Owner Sitemap
- /owner dashboard P17, /owner/users/manual-add, /owner/users/bulk-import, /owner/users/{id}/reset-password envelope, /owner/audit-logs, /owner/analytics/full, /owner/system/read-only, /owner/settings

## Common
- /403 Forbidden, /404 Not Found, /offline, /health (intranet detection)

## Navigation Components V9
- Bottom Nav Student mobile: Dashboard, Scheduler, Resources, Inbox unread badge, More (Tickets, Curriculum, Settings)
- Top AppBar: Logo, Brand, Semester badge, Global state badge, Polling status badge Intranet/Offline/Online, Notifications bell unread count, Profile avatar dropdown
- Sidebar Desktop same
- Sidebar Staff: Dashboard, Courses/Specs, Resources, Messages, Tickets, Curriculum, Forms, Calendar, Users Admin, Audit Owner, Settings

## Auth Guards V9
- All routes require auth except /login, Role guard checks role matrix redirect /403, Onboarding guard if must_change_password or first_name null redirect /onboarding, Grace guard Phase A grace active /scheduler/enrolling still accessible but add disabled

END P00 V9
