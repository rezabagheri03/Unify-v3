# P06 Student Resource Hub - V9 Shared Host (Local Filesystem 10GB Shop Plan)

## Routes
/resources list, /resources/{id} detail, /resources/upload student upload, /resources/my-uploads my pending/approved

## List Page /resources
Query Params course_id professor_id specification_id search sort newest/oldest/rated/downloaded badge filter page, UI Top Search bar + Filter bar Course dropdown Professor dropdown Badge type multi-select Sort dropdown My Uploads toggle, Tabs All Cached (files cached via Cache API) My Uploads, List Virtualized FileCards 20 per page infinite scroll FileCard detailed cache status icon pin icon, Empty No resources illustration + button upload if student, Fab Button "آپلود جزوه" student and professor

### Data Fetching V9
GET /api/v1/resources?course_id=&professor_id=&specification_id=&sort=newest&search=&page=, Cached resources from Cache API FileCacheMeta IndexedDB for offline filter, Workbox runtime cache metadata 5min

### Actions
Click FileCard -> detail, Download button in card direct download without detail, Pin button Pin/unpin file to prevent LRU eviction POST /api/v1/file-cache/{id}/pin {pinned bool} local only IndexedDB, Filter from Dashboard prefilled course+prof

## Detail Page /resources/{id}
UI Header Title large bold Author Shamsi date Badge Version Download Count Rating avg + count, Tabs Preview/Info Rating Sticky Note Versions, Preview/Info Tab File preview For PDF pdf.js first page preview For DOCX icon + download to view, Description full, Course+Professor evergreen link, Specification context if exists, Buttons Download primary Rating star Add Sticky Note, Cache status If cached "کش شده" green check size last accessed pin toggle, If not cached offline cloud icon + "برای دانلود نیاز به اینترنت است - فایل کش نشده", Versions tab link "مشاهده نسخه‌ها (3 نسخه)"
Rating Tab Average star large + count + distribution chart bars 5-1, My Rating section If rated shows my stars edit else rating input 1-5 + submit button shows "شما آپلودکننده هستید" if self, Optional snackbar after 30s viewing triggers rating input highlight
Sticky Note Tab Textarea private note max1000 Save Delete Private badge "فقط شما"
Versions Tab List versions family sorted version desc row version number upload date Shamsi uploader changelog download button if file exists badge old/new scheduled hard delete date for old versions

### Data Fetching V9
GET /api/v1/resources/{id}, GET /api/v1/resources/{id}/rating/me, GET /api/v1/resources/{id}/sticky-note, GET /api/v1/resources/{id}/versions

### Actions V9 Shared Host
Download GET /api/v1/resources/{id}/download direct file /uploads/resources/{course}/{prof}/{uuid}.pdf not signed S3 via Nginx direct, increments download_count, caches via Cache API saves to cache dir, updates LRU last_accessed, success snackbar "دانلود شد - کش شد"
Rating POST /api/v1/resources/{id}/rating {rating} updates average via Laravel observer, Sticky Note POST /api/v1/resources/{id}/sticky-note {note} encrypted via Crypt::encryptString, Delete Sticky DELETE, Pin Local toggle IndexedDB, Upload New Version Only professor/admin visible button -> modal file picker -> POST /api/v1/resources/{id}/new-version multipart file + changelog

### Offline V9
Detail shows cached info Workbox, download cached file via Cache API native viewer, rating/sticky queued IndexedDB

## Upload Page /resources/upload
Form Course dropdown required any course+prof evergreen but student restrict own dept courses, Professor dropdown required professors teaching that course, Title required max255 Description optional max1000 File picker drag-drop area shows file name size mime icon validation, Checkbox "ارسال نوتیفیکیشن به همکلاسی‌ها" default false, Submit button "آپلود" loading, After submit success "جزوه در انتظار تایید است" navigate my-uploads
API POST /api/v1/resources/upload multipart title description course_id professor_id specification_id optional file notify bool, Validation Course+prof required title required file required PDF/DOCX max50MB magic finfo quota 5/day check MySQL 429

## My Uploads Page /resources/my-uploads
Tabs Pending Approved Rejected, List FileCards status badge reason if rejected download count if approved, No rating own? Rating allowed but flagged, Actions Edit description? Allow edit title/desc for pending only PATCH
