# P02 Student Dashboard - V9 Shared Host (Polling + local file cache)

## Route /

### Purpose
Smart Dashboard scrollable vertical list Course Specification cards current semester, archive dropdown, automated alerts via polling

### UI Layout V9
AppBar Logo Brand Semester badge "ترم جاری: 1403-1" Global state badge "حالت: ثبت‌نام" Polling status badge Intranet/Offline/Online (no WebSocket badge), Notifications bell unread count, Profile avatar
Top Archive Dropdown Select "ترم جاری" default or past semesters where archived enrollments exist, On change loads archived view read-only
Alert Banners Stack critical: Grace countdown banner if active, Schedule conflict banner if spec change caused conflict "تداخل به دلیل تغییر {course}" red (detected via polling every 15s), Honor status banner yellow "وضعیت خوداظهاری: ترم آخر", Offline banner red if offline, Intranet banner yellow if intranet mode detected via health check internal reachable external not
Course Cards List Virtualized react-window list enrolled finalized specs current (or archived if archive selected), If no enrollments Empty state illustration + button "رفتن به ثبت‌نام" if Phase A else "برنامه شما خالی است"

### Course Card Component V9 Same but polling
Header Custom colored background deterministic hash professor_id hue, Body Day+Time clock icon Location icon Credits badge Exam date small, Notice Banner if active high priority first notice title priority color, Footer Action Buttons 3 Download Resources navigates /resources?course_id={courseId}&professor_id={profId} filtered evergreen, Class Group icon telegram opens external browser confirmation dialog, Details icon info opens modal
Context menu 3 dots Mute/unmute notifications for this spec

### Details Modal
Title Course+Professor Tabs Info Exams Notices FAQ, Info Code Credits Dept Professor personnel ID Day Time Location Telegram Link clickable external, Exams Final Date Shamsi+Gregorian Time Midterm orange badge, Notices List active notices for this spec, FAQ List FAQ for this spec, Close

### Data Fetching V9
GET /api/v1/enrollments?semester=current&status=finalized list spec ids, GET /api/v1/specifications?ids=... or include specs in enrollment response expanded, GET /api/v1/specifications/{specId}/noticeboard?active_only=true for banner, Polling every 15s GET /api/notifications/unread for spec_updated, spec_deleted, phase_changed -> updates list real-time 15s delay + shows alert banner

### States
Loading skeleton cards, Empty, Error retry, Archive view read-only gray overlay

### Offline V9
Cached enrollments + specs + notices viewable via Workbox runtime cache, download resources cached only via Cache API, external telegram link requires online but shows offline message if offline

### Edge V9
Spec time/location changed while viewing dashboard -> Polling 15s later updates card + critical banner if conflict, Spec deleted -> card removed + polling notification "مشخصه {course} لغو شد" + alert banner, Grace active: Dashboard shows countdown, no add/remove here
