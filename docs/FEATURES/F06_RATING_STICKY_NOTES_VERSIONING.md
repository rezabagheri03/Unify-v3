# F06 Rating Sticky Notes Versioning - V9 Shared Host

## Rating Fixed
After download + 30s viewing file timer client, shows non-blocking snackbar bottom "مایلید به این جزوه امتیاز دهید؟" star 1-5 + buttons "بعدا"/"ثبت امتیاز" Not forced popup
User can rate anytime detail page rating section
POST /api/v1/resources/{id}/rating {rating 1-5} idempotency key MySQL
Server: Check approved, create/update ResourceRating row UNIQUE(student_id, resource_family_id) new replaces old, is_self_rating = uploader_id == student_id
Recalculate average: SELECT AVG(rating) WHERE resource_family_id=family AND is_self_rating=0 (exclude self to prevent inflation). If no non-self average 0 "بدون امتیاز". rating_count count non-self. Store in Resource rows family denormalized via trigger or Laravel observer update all versions in family same average
UI detail shows average star + count + distribution chart 5 bars, My rating section shows current if rated with edit, own rating flagged "شما آپلودکننده هستید" with different color, Professor view feedback distribution but not who rated privacy

## Sticky Notes Private
Detail tab "یادداشت شخصی" textarea + save + delete, max 1000 chars, GET /api/v1/resources/{id}/sticky-note returns own note, POST create/update UNIQUE(student_id, family_id), stored encrypted at rest via Crypt::encryptString with APP_KEY, only creator can view via API check student_id==creator, local IndexedDB encrypted? Actually IndexedDB no encryption but device locked, server encrypted, UI private badge "فقط شما می‌بینید", Delete button

## Versioning Family Concept
family_id = id first version, every version row has family_id + version + previous_version_id, ratings and sticky linked to family_id not version id preserved
Upload New Version only professor matching course+prof or Admin, file picker + changelog optional, POST /api/v1/resources/{id}/new-version file + changelog, Server validate, create new row family_id old.family_id version old.version+1 previous_version_id old.id file new permanent path status approved if professor, old is_superseded=1 scheduled_hard_delete_at now+30d badge "نسخه قدیمی", new is_superseded=0 current, notification optional enrolled, ratings preserved family_id
Viewing Versions tab list family sorted version desc version number upload date Shamsi uploader changelog download button if file exists, after hard deleted after 30d via cron daily old file content deleted file_path null is_deleted_content=1 row shows "فایل حذف شده" not downloadable metadata remains
Hard Delete Old Version Job Cron daily checks scheduled_hard_delete_at < now and is_superseded 1 -> deletes file content /uploads, sets file_path null is_deleted_content=1, AuditLog

## API V9
POST /api/v1/resources/{id}/rating, GET /api/v1/resources/{id}/rating/me, POST /api/v1/resources/{id}/sticky-note, GET /api/v1/resources/{id}/sticky-note, DELETE /api/v1/resources/{id}/sticky-note, POST /api/v1/resources/{id}/new-version, GET /api/v1/resources/{id}/versions

## Edge
Rating old version then new uploaded rating remains visible new version because family_id same, sticky same, old version download after scheduled delete 410 Gone
