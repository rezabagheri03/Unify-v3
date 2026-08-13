# F05 Resource Hub Evergreen - V9 Shared Host (Local Filesystem 10GB Shop Plan)

## Evergreen
Linked to (course_id, professor_id) not semester, optional specification_id context, when semester soft hides old specs is_active 0 resources remain accessible via filtered course+prof

## File Constraints V9
Types PDF application/pdf and DOCX application/vnd.openxmlformats-officedocument.wordprocessingml.document only magic bytes finfo not extension, Max 50MB per file, ClamAV optional, Storage local /uploads/resources/{course_id}/{professor_id}/{uuid}.{ext} on Cloud Host, not S3, client Cache API 100MB LRU via Workbox eviction, professor protected never evicted pin, user can pin, Server no per-user limit but client 100MB, student quota 5/day rolling window via MySQL table ResourceUploadCount user_id date count (no Redis)

## File Card
Icon PDF red DOCX blue, Title bold, Author, Shamsi date, Average rating excluding self, Download count, Badge professor purple expert blue admin green, Version vN, Cache status cloud not cached check cached, Pinned icon, Description truncated 100 expandable, Click detail

## Upload Workflow V9 Shared Host
1. Student select file title required desc optional course dropdown + professor dropdown evergreen, checkbox "ارسال نوتیفیکیشن به همکلاسی‌ها" default false
2. Client validation MIME+size
3. File stored temp /tmp or Capacitor staging
4. Metadata queued IndexedDB SyncQueue idempotency key pending
5. When online POST /api/v1/resources/upload multipart file stream + metadata, server saves to temp bucket /storage/app/temp, creates Resource row status pending version 1 file_path temp file_size mime uploader_id course_id prof_id spec_id nullable average 0, notifies approvers professors course + experts dept via polling + Pushe PHP curl "جزوه جدید در انتظار تایید"
6. Approval Professor/Expert/Admin sees pending queue previews file direct file path, clicks Approve -> moves file from temp to permanent /uploads/resources/.../uuid.pdf, status approved badge_type, scheduled_hard_delete_at null, notifies uploader "تایید شد" + if checkbox true notifies enrolled students course+professor "جزوه جدید برای درس X"
7. Rejection status rejected reason stored notifies uploader

Professor upload: status approved immediately badge professor file directly permanent optional notification checkbox default true broadcast to enrolled

## Versioning V9 Local FS
Button "آپلود نسخه جدید" detail page visible professor owning course+prof or Admin, file picker + changelog, POST /api/v1/resources/{id}/new-version multipart file + changelog, Server validate professor owns course+prof or admin, create new Resource row family_id old.family_id version old.version+1 previous_version_id old.id file to new permanent path, old resource is_superseded=1 scheduled_hard_delete_at now+30d badge "نسخه قدیمی", new is_superseded=0 current, notification optional to enrolled, ratings preserved via family_id
Viewing Versions tab list all versions family sorted version desc version number upload date Shamsi uploader changelog download button if file exists, after old hard deleted after 30d via cron daily old version row shows "فایل حذف شده" not downloadable metadata remains
Hard Delete Old Version Job Cron daily checks scheduled_hard_delete_at < now and is_superseded=1 -> deletes file content from /uploads, sets file_path null is_deleted_content=1 keeps row for audit, AuditLog deletion

## Download Flow V9
Click Download -> GET /api/v1/resources/{id}/download -> checks status approved, increments download_count, returns direct file path /uploads/resources/... (no signed S3 URL) via Nginx X-Accel or direct, client downloads via Fetch + Cache API saves to cache dir, updates LRU last_accessed, success snackbar "دانلود شد - کش شد"
Offline: If cached opens cached file via Cache API, no server call banner "حالت آفلاین - فایل کش شده", If not cached offline shows cloud icon + "برای دانلود نیاز به اینترنت است"

## Filtering Sorting
Filters Course Professor Badge My uploads Version family Search title/author fulltext MySQL FULLTEXT, Sort Newest Oldest Highest Rated Most Downloaded, Pagination 20

## Smart Notification V9 Polling + Pushe
On new approved resource: If uploader checked notify, Laravel job fans out polling notification + Pushe API curl to enrolled students where enrolled semester=current and course_id+professor_id matches spec course+prof, except muted NotificationMute check

## API V9
GET /api/v1/resources?course_id=&professor_id=&specification_id=&sort=&search=&page=
GET /api/v1/resources/{id}
GET /api/v1/resources/{id}/download
POST /api/v1/resources/upload multipart
POST /api/v1/resources/{id}/new-version
POST /api/v1/resources/{id}/approve
POST /api/v1/resources/{id}/reject {reason}
DELETE /api/v1/resources/{id}/hard-delete (Admin)
GET /api/v1/resources/pending

## Offline V9
List cached resources viewable via Workbox runtime cache, download cached only, upload queued IndexedDB

## Edge
Student uploads 6th file today 429 quota, exe renamed pdf magic bytes fails 400, professor new version while old pending delete allowed old scheduled delete still 30d, file cache full 100MB new 60MB eviction LRU non-protected, still not enough error "حافظه کش پر است", resource hard deleted by Admin while viewing detail 404
