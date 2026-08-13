# F13 NoticeBoard & FAQ - V9 Shared Host

## Data Models MySQL
NoticeBoard id UUID, specification_id FK, title VARCHAR(255), content TEXT, priority ENUM low medium high, banner_color VARCHAR(7) nullable, created_at DATETIME, expires_at DATETIME nullable, created_by FK
FAQ id UUID, specification_id FK, question TEXT, answer TEXT, is_pinned BOOL, created_at, created_by

## Roles
Professor CRUD NoticeBoard + FAQ own specs, Student view banners on course card + dedicated pages per spec, Expert/Admin/Owner read-only

## NoticeBoard Flow Professor
List per spec table title priority badge color low gray medium blue high red/orange banner_color preview created_at Shamsi expires_at is_expired actions Edit/Delete
Create Title required max100 Content required max1000 Priority dropdown low/medium/high default medium Banner Color picker hex optional (default per priority) Expires At Shamsi optional if set banner disappears after expiry, On save creates row triggers polling + Pushe if priority high push "اطلاعیه جدید برای درس {course}: {title}" low/medium no push only in-app banner

## NoticeBoard Flow Student
Course Card Banner: Dashboard course card if spec has active NoticeBoard where expires_at null or >now show banner top card title + priority color + banner_color background truncated content expandable Click banner navigates to NoticeBoard detail per spec list
Dedicated Page per spec list all active notices sorted priority high first then newest title content priority badge created Shamsi expires countdown if set, Expired toggle "نمایش منقضی شده‌ها"
Offline cached Workbox 1h

## FAQ Flow Professor
List per spec table question truncated answer truncated is_pinned, Create Question max500 Answer max2000 Is Pinned checkbox, Edit/Delete, Pinned sorted pinned first then newest

## FAQ Flow Student
Page per spec list FAQ accordion question bold clickable expands answer pinned badge "سنجاق شده" yellow, search Q/A, offline cached

## API Laravel
GET /api/v1/specifications/{spec_id}/noticeboard?active_only=true
GET /api/v1/noticeboard/{id}
POST /api/v1/specifications/{spec_id}/noticeboard Professor {title, content, priority, banner_color, expires_shamsi}
PATCH /api/v1/noticeboard/{id}
DELETE /api/v1/noticeboard/{id}
GET /api/v1/specifications/{spec_id}/faq
GET /api/v1/faq/{id}
POST /api/v1/specifications/{spec_id}/faq Professor {question, answer, is_pinned}
PATCH /api/v1/faq/{id}
DELETE /api/v1/faq/{id}

## Notifications V9
High priority notice Pushe + polling to enrolled, Low/medium in-app only, FAQ no push
