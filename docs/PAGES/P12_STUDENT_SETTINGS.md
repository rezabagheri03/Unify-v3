# P12 Student Settings - V9 Shared Host (Polling + Pushe + IndexedDB)

## Route /settings + sub-routes

### Main Settings Page /settings
Sections list icons Theme & Appearance ->/settings/theme Notifications ->/settings/notifications Profile ->/settings/profile Password ->/settings/password Offline Queue ->/settings/offline-queue Intranet Status ->/settings/intranet-status About & Version

### Theme & Appearance /settings/theme
Theme Presets 5 presets color preview circles selected checkmark name, Dark Mode toggle switch, Department Default info "تم پیش‌فرض دانشکده شما: {theme} - شما می‌توانید تغییر دهید", Preview area sample CourseCard with selected theme, Save POST /api/v1/users/me/preferences {theme_id, dark_mode} via Laravel, Offline theme saved locally IndexedDB immediate

### Notifications /settings/notifications
Global Push toggle Enable/disable all push stored local + server DeviceToken is_active, SMS Fallback toggle "دریافت SMS برای اطلاعیه‌های حیاتی" checkbox shows mobile field if no mobile set link to profile to add mobile mobile stored but not visible staff only for SMS Kavenegar, Per-Spec Mute List enrolled specs current semester with toggle mute/unmute per spec search Each row spec course name professor mute toggle POST /api/v1/notifications/mute {specification_id, muted}, Notification History link to /inbox, Test Push button POST /api/v1/notifications/test sends test push via polling + Pushe to own devices

### Profile /settings/profile
Shows Student Number read-only First Name editable once per semester Last Name editable once per semester Department read-only Academic Status Declared current with banner Supplementary Details textarea optional max500 "اطلاعات تکمیلی که می‌خواهید اساتید ببینند (اختیاری) - مثلا شماره تماس در صورت تمایل" Mobile field nullable not visible staff Email nullable not visible staff Save PATCH /api/v1/users/me {first_name, last_name, supplementary_details, mobile, email} Validation Name max100 supplementary max500 mobile regex Iran email regex Once per semester edit limit for first/last name enforced via last edit timestamp check error "نام فقط یک بار در هر ترم قابل ویرایش است"

### Password /settings/password
Form Old Password New Password Confirm Complexity indicator Checkbox "خروج از سایر دستگاه‌ها" default true Button "تغییر رمز" success snackbar "رمز با موفقیت تغییر کرد" POST /api/v1/password/change

### Offline Queue /settings/offline-queue
Page shows IndexedDB syncQueue list with status pending/syncing/synced/failed/conflict Columns Entity Type icon Action Summary Created Shamsi Status badge Attempts Last Error Actions Retry failed Delete pending Cancel Resolve conflict opens conflict resolver modal Top stats Pending count Last sync time Button "همگام‌سازی اکنون" forces sync if online via Workbox Background Sync, Button "پاک کردن کش فایل‌ها" clears Cache API except pinned/protected Button "حذف و بازسازی دیتابیس لوکال" deletes IndexedDB and re-fetches from server on next online dangerous confirmation typing "حذف"

### Intranet Status /settings/intranet-status
Shows current connectivity isOnline isIntranetMode isOffline with colored badges icons, Details Internal server reachable true/false with latency External reachable true/false Polling connected true/false with url Push provider active Pushe Last health check time, Button "بررسی مجدد اتصال" triggers health check GET /api/v1/health, Info text intranet explanation "در حالت اینترانت، اعلان‌ها از طریق پولینگ هر ۱۵ ثانیه + پوشه اندروید ارسال می‌شود - برای iOS حذف شده - برای اطلاعیه‌های حیاتی SMS را فعال کنید", SMS opt-in status

### About & Version
Shows App version Build number Backend version from /api/v1/health Device info User ID Role Department Last login Shamsi Storage usage file cache size / 10GB Shop plan limit

### Data Fetching V9
GET /api/v1/users/me, GET /api/v1/users/me/preferences, GET /api/v1/notifications/mutes, GET /api/v1/sync/status (polling), GET /api/v1/health

### Offline V9
All settings pages work offline except password change profile name edit requires online? Actually profile edit queued IndexedDB, offline queue page local only
