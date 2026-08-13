# P13 Professor Pages - V9 Shared Host (Laravel + Local Filesystem)

## Routes
/professor dashboard, /professor/specs/{specId}/students, /professor/resources own + pending, /professor/resources/{id}, /professor/resources/upload, /professor/messages, /professor/noticeboard/{specId}, /professor/faq/{specId}, /professor/settings

## Dashboard /professor
UI AppBar same + archive toggle past read-only, Stats Total Specs Current Total Enrolled Sum Total Resources Uploaded Avg Rating, Spec Cards Course Spec with enrolled count resource count pending approval count avg rating Buttons Students Resources Messages NoticeBoard FAQ, Quick Actions Upload Resource View Pending Approvals Broadcast Message, Data GET /api/v1/professor/specifications?semester=current (own only) GET /api/v1/professor/stats cached offline viewable but stats require online, Polling for new student enrollments? Polling every 30s

## Students List /professor/specs/{specId}/students
Header Course Name + Spec details Day/Time/Location, Table Student ID searchable Name Academic Status Declared with honor flag icon final_semester Supplementary Details free text if contact Enrollment Status finalized Enrolled At Shamsi Banned badge, Actions per row Send Private Message button opens compose modal, Export Excel button exports list to Excel own spec, Search name/id, Data GET /api/v1/specifications/{specId}/students professor own spec only, Offline cached Workbox

## Resources Pages /professor/resources
List Own Resources Tabs My Resources approved own + Pending Student Notes queue, My Resources FileCards own with version rating download count actions Edit Description Upload New Version Request Delete, Pending Queue List pending own course+prof uploader student ID/name title file preview button direct file temp path Approve/Reject buttons Approve badge professor status approved notifies student + enrolled if notify checkbox via polling + Pushe PHP curl, Upload Center Form Course dropdown own dept auto prof=self Title required Description optional File PDF/DOCX max50MB Notification checkbox default true "ارسال اعلان به دانشجویان این درس" Submit POST /api/v1/resources/upload immediate approved badge professor file to /uploads/resources/{course}/{prof}/{uuid}.pdf AuditLog, Offline requires online (file large), Detail Same student detail but extra rating distribution download count no who preview, Actions Edit desc Upload New Version Request Delete

## Messages Page /professor/messages
Tabs Broadcast History messages sent to specs Private Chats 1-to-1 threads with students, Broadcast History List broadcast messages sent each shows spec course subject body preview sent at edited badge deleted placeholder edit/delete buttons if own, Private Chats List threads with students each thread student name last preview unread count click thread chat bubbles, Compose Buttons New Broadcast select spec dropdown own specs subject body rate limit 1 per 10min via MySQL cache table, New Private search student ID/name own enrolled students subject body, Thread view chat same student but with edit/delete own messages, Data GET /api/v1/messages?sent_by_me=true&tab=broadcast/private POST /api/v1/messages/send with specification_id for broadcast or recipient_id for private PATCH edit DELETE soft delete, Polling for new replies every 15s

## NoticeBoard CRUD /professor/noticeboard/{specId}
List per spec Table title priority badge created at expires at actions Edit/Delete, Create/Edit modal Title required Content required Priority low/medium/high Banner Color picker hex optional Expires At Shamsi optional, On save POST /api/v1/specifications/{specId}/noticeboard, High priority push via Pushe + polling

## FAQ CRUD /professor/faq/{specId}
List FAQ Table question truncated answer truncated pinned badge actions Edit/Delete, Create/Edit modal Question required Answer required Is Pinned checkbox, On save POST

## Settings /professor/settings Same as student settings but notification mute not per spec? Actually professor doesn't mute own spec but can mute student? No

## Offline V9
Dashboard cached student list cached resources list cached pending approval requires online messaging cached but send requires online broadcast requires online (rate limit) private reply queued IndexedDB? For professor private queued but broadcast not queued to avoid spam confusion requires online

## Edge V9 Shared Host
Professor tries view other professor spec student list 403, Upload exe renamed pdf blocked magic finfo, Broadcast spam 2 within 10min 429, Edit message after push placeholder push irreversible info in edit modal
