# F17 Audit Logging & Security - V9 Shared Host (Laravel + MySQL + cPanel)

## AuditLog MySQL
id CHAR(36) PK UUID, user_id FK nullable, action ENUM deletion major_edit password_reset role_change ban honor_status_change final_semester_abuse_flag login failed_login file_upload file_approval message_edit_delete ticket_status_change, resource_type VARCHAR(50), resource_id VARCHAR(100), timestamp DATETIME default now, ip_address VARCHAR(45) X-Forwarded-For, user_agent TEXT, details JSON encrypted via Crypt::encryptString with APP_KEY (Laravel encrypter), is_suspicious BOOL

## What Logged Automatically Middleware
All DELETE -> deletion, All PATCH/PUT sensitive fields role is_banned academic_status_declared password -> major_edit or specific, POST password_reset -> password_reset, POST role assignment -> role_change, POST ban/unban -> ban, POST honor status -> honor_status_change, Flag final_semester abuse -> final_semester_abuse_flag, Login success -> login, Login failure -> failed_login, Resource upload/approve/reject/new-version/hard-delete -> file_upload/file_approval/deletion, Message edit/delete -> message_edit_delete, Ticket status change/assign/escalate -> ticket_status_change

## Middleware Implementation Laravel
Middleware AuditLogMiddleware intercepts response, if method DELETE or path contains /admin and status 2xx and action sensitive creates AuditLog row via queued job or sync to not block response, For file uploads hook after file saved, For auth hook in login controller

## Encryption at Rest V9
details field encrypted via Crypt::encryptString with key APP_KEY (32 chars from .env), Even DB admin cannot read without APP_KEY, Key from env never logged

## Viewer Owner Only
Page Audit Logs filters user_id action resource_type resource_id timestamp range from/to Shamsi pickers is_suspicious toggle IP search, Table Timestamp Shamsi+Gregorian sortable Actor user ID/name Action badge color Resource Type/ID IP User Agent truncated Details button Suspicious red badge, Details Modal Decrypted details JSON pretty diff old/new, Export CSV/Excel filtered requires reason modal + AuditLog export action itself, Pagination 50

## Retention
Online 2 years, then archive to cold storage table AuditLogArchive or S3 JSON dump or local file /storage/backups/audit, still viewable via archive toggle

## Security V9 Shared Host

### Password Hashing
Argon2id via Hash::make driver argon2id, pepper env PASSWORD_PEPPER added via HMAC, Password history table PasswordHistory user_id hash created_at keep last 3 check new not in last 3

### Rate Limiting (No Redis, MySQL cache)
Login 5 per 15 min per IP and per username via Laravel throttle middleware file cache driver or MySQL, 429 Retry-After, Targeted messaging 10/min per expert 20/min admin 20/min owner, Broadcast 1/10min per professor per spec, Ticket create 5/day per student, Resource upload 5/day, Excel import 1/10min, Password reset 3/hour per operator

### JWT Sanctum Sessions
Sanctum SPA cookie httpOnly secure SameSite Strict + CSRF token X-CSRF-Token double submit, Staff session max 12h inactivity logout, DeviceToken table for push is_active false when expired

### File Upload Security Shared Host
Magic bytes finfo not extension, Max sizes per type, ClamAV optional via cron, Block exe bat sh dll so dylib blacklist, Path traversal prevention sanitized filename UUID only, Store outside public_html? Actually storage/app/public linked to public_html/uploads via symlink, but file path not direct user input

### External Links
Telegram links open external browser confirmation dialog "آیا می‌خواهید به تلگرام بروید؟" URL, not WebView XSS, Form download via download manager direct file /uploads/forms/...

### Intranet Compliance Security Shared Host
No internal WebSocket self-signed needed now polling only HTTP, Pushe API key env, SMS Kavenegar API key env, All inside Iran

## Edge
AuditLog write fails should not block main request log error to laravel.log retry via queued job, Decryption fails wrong key show "خطا در رمزگشایی", Rate limit file cache down fail closed for login block to prevent brute force log error
