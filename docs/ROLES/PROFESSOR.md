# ROLE: PROFESSOR - V9 Shared Host (Laravel)

## Identity
- ID = Personnel ID, role=professor, department_id NOT NULL, scope own department but view own specs only current + archive read-only

## Permissions
- CAN: View own specs current only + archive read-only past, upload resources auto-approved professor badge, upload new version, view average rating excluding self + rating count + download count own courses, view enrolled students list per spec (student ID, name, academic_status_declared honor flag), send broadcast to whole class spec rate limit 1/10min via MySQL cache, send 1-to-1 private, edit/delete own messages (is_edited, is_deleted placeholder push irreversible), manage FAQ/NoticeBoard CRUD per spec with priority banner color expiry, approve/reject student resources own course
- CANNOT: CRUD courses/specs (Expert), prereq/coreq, curriculum charts, forms, tickets, ban, set semester, view other profs specs, audit logs, hard delete (Admin)

## Dashboard
- Header current semester + global_state badge + archive toggle past read-only
- Stats: Total specs current, total enrolled sum, total resources uploaded, avg rating
- Spec cards: Course Name bold, code, day Persian, time, location, enrolled count, resource count, rating avg, buttons Students, Resources, Messages, Notice, FAQ

## Upload Center
- Form: Course dropdown own dept, auto prof=self, Title required, Description optional, File PDF/DOCX magic finfo max 50MB, Notification checkbox default true (if true, after approval server calls Pushe API via curl + polling)
- POST /api/v1/resources/upload immediate status approved badge professor, file to `/uploads/resources/{course}/{prof}/{uuid}.pdf`, AuditLog
- Upload New Version: detail button "آپلود نسخه جدید" -> file picker + changelog -> POST /api/v1/resources/{id}/new-version, old scheduled_hard_delete_at now+30d via cron daily, ratings preserved via family_id

## Resource Management
- Tabs My Resources approved own + Pending Student Notes queue
- My Resources FileCards version rating downloads actions Edit Description Upload New Version Request Delete
- Pending queue: List pending own course+prof, uploader student, title, preview signed? Direct file temp path, Approve/Reject, Approve badge professor, notifies student + enrolled if notify checked

## Student List
- Per spec table student_id searchable name academic_status_declared honor flag icon final_semester supplementary_details free text if contact enrollment status finalized enrolled_at Shamsi banned badge, Export Excel own spec

## Class Messaging
- Broadcast: Compose to whole class spec dropdown own specs subject body rate limit 1/10min via MySQL, POST /api/v1/messages/send with specification_id, push to enrolled via polling + Pushe PHP curl
- Private: Compose to specific student search own enrolled, recipient_id, subject body
- Edit PATCH /api/v1/messages/{id} only sender=self sets is_edited, Delete sets is_deleted placeholder, WebSocket? No, polling shows placeholder

## NoticeBoard & FAQ CRUD per spec
- Notice: Title max100, Content max1000, Priority low/medium/high, Banner color hex optional, Expires At Shamsi optional, if priority high push via Pushe, low/medium in-app only
- FAQ: Question max500 Answer max2000 Is Pinned checkbox, pinned first

## Settings
- Theme, Profile own name editable once per semester, Password, Offline Queue

## Offline V9
- View cached student list, resources, messages cached, upload requires online (file large), broadcast requires online

END PROFESSOR V9
