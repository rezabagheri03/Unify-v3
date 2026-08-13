# F10 Forms Repository - V9 Shared Host (Local Filesystem 10GB)

## Data Model MySQL
Form id UUID, title VARCHAR(255), description TEXT, file_path TEXT /uploads/forms/{dept}/{uuid}.pdf, file_size BIGINT, department_id FK, is_university_level BOOL, signature_guide VARCHAR(200) one-line, is_active BOOL, created_at, created_by

## Roles
Expert CRUD own dept forms is_university_level false, Head same, Admin CRUD univ is_university_level true + dept any, Owner read-only, Student view download own dept + univ

## Flow Expert/Admin
List own dept forms + univ read-only Expert all Admin, Create Title required Description optional File upload PDF/DOCX 20MB magic finfo signature_guide one-line required max200 Is Active toggle default true, On save file to /uploads/forms/{dept}/{uuid}.pdf creates Form row AuditLog, Edit update title/desc/file/guide, Delete hard delete file + row AuditLog deletion

## Flow Student
Tabs Department Forms own dept + University Forms, Search title, List card Title Description truncated Signature guide badge "راهنما: امضا مدیر گروه + مهر آموزش" Download button File size Date Shamsi, Download GET /api/v1/forms/{id}/download direct file /uploads/forms/... via Nginx, Cache API, offline indicator cloud not cached check cached

## API Laravel
GET /api/v1/forms?department_id=&is_university_level=&search=&page=, GET /api/v1/forms/{id}, GET /api/v1/forms/{id}/download, POST /api/v1/forms multipart, PATCH /api/v1/forms/{id}, DELETE /api/v1/forms/{id}

## Offline V9
List cached Workbox runtime cache 1h, download cached only Cache API

## Notifications
On new form university-level polling + Pushe low "فرم جدید دانشگاهی: {title}", dept form push to students own dept

## Edge
Form file deleted hard 404, Guide empty error
