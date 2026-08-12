# F20 Grace Period 24h - V9 Shared Host (Laravel Scheduler + Cron)

## Purpose
When Admin switches phase enrolling->active students who haven't finalized get 24h grace period after that temporary list wiped and schedule empty

## Trigger
Admin switches global_state enrolling -> active via PATCH /api/v1/admin/semesters/{id}/global-state {global_state=active}
Server: Checks if current global_state is enrolling if not error 400 "فقط از حالت ثبت‌نام می‌توان به فعال تغییر داد"
Confirmation modal requires admin typing semester name

## Server Logic V9 Laravel + Cron (No Celery)
1. Set Semester.global_state=active, grace_period_ends_at=now+24h Asia/Tehran stored UTC (Asia/Tehran), grace_period_handled=0
2. Create AuditLog major_edit old enrolling new active + grace_period_ends_at
3. Cron job grace_period_countdown: Laravel Scheduler every minute checks if now>=grace_period_ends_at and global_state active and handled=0 (in app/Console/Kernel.php $schedule->command('enrollments:wipe-grace')->everyMinute())
4. Immediately send notification to all students who have temporary enrollments where semester=current and status=temporary: polling notification + Pushe via PHP curl "فاز ثبت‌نام به فعال تغییر کرد - 24 ساعت مهلت نهایی‌سازی دارید" priority critical
5. Also polling event phase_changed {old enrolling new active grace_ends_at} to all clients via Notification table, client updates banner via polling

## Client UI During Grace Period
Phase A page still accessible but Add new temp disabled (only finalize existing temp allowed), Banner top red countdown timer live updating every second "مهلت نهایی‌سازی: 23:59:45" when <2h color red flashing solid red, Final Submit button remains enabled for those with temp list, shows warning "پس از مهلت لیست موقت حذف می‌شود", When grace ends banner changes to "مهلت تمام شد - لیست موقت حذف شد" if student had temp and didn't finalize

## Cron Job Wipe After 24h (Laravel Command enrollments:wipe-grace)
Every minute Scheduler checks semesters where global_state active and grace_period_ends_at <= now() and handled=0
For each such semester:
1. Hard delete Enrollments where semester_id=current and status=temporary (wipe)
2. Set grace_period_handled=1
3. For each student whose temp was wiped (had at least one temp) send notification polling + Pushe "لیست موقت شما پس از پایان مهلت 24 ساعته حذف شد - برنامه شما خالی است - با آموزش تماس بگیرید" critical
4. AuditLog major_edit "Grace period ended - wiped temporary enrollments count X"

## Validations
Cannot start grace if already active, second switch enrolling->active not possible because already active, but if admin switches active->enrolling rollback then again enrolling->active new grace starts anew, Cannot define new semester while grace active blocked check in semester creation, Student tries to add new temp during grace -> 403 "فاز ثبت‌نام بسته شده - فقط نهایی‌سازی لیست موجود ممکن است", Student tries to finalize after grace ends -> 403 "مهلت نهایی‌سازی تمام شده" buffer 5 seconds for clock skew

## API Laravel V9
PATCH /api/v1/admin/semesters/{id}/global-state {global_state}
GET /api/v1/semesters/current includes grace_period_ends_at handled time remaining
GET /api/v1/enrollments/temp list temp during grace
POST /api/v1/enrollment/final allowed during grace for those with temp

## Notifications V9 Polling + Pushe
Start grace: Polling + Pushe to all with temp
2h remaining: Polling + Pushe critical to those still not finalized with temp
End grace: Polling + Pushe to those wiped

## Edge
Server restarts during grace period: Cron on next minute checks grace_period_ends_at and resumes, Timezone Asia/Tehran stored UTC countdown Asia/Tehran, Student finalizes exactly at grace end second allow if request timestamp <= grace_ends + 5 seconds, Admin switches active->exam during grace should be blocked "ابتدا باید مهلت 24 ساعته تمام شود" only allow enrolling->active and active->exam after grace handled true

## Audit
Phase change logged major_edit with grace period times
