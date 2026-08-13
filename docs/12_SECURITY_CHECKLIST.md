# 12 - Security Checklist - V9 Shared Host (Laravel + MySQL + cPanel)

This checklist an agentic LLM must verify before saying DONE. Each item must be checked.

## Authentication & Password

- [ ] Password hashing Argon2id via `Hash::make` with driver `argon2id` in `config/hashing.php`, not bcrypt
- [ ] Pepper from env `PASSWORD_PEPPER` added via HMAC before hashing (optional but recommended)
- [ ] PasswordHistory table keep last 3 hashes, check new password not in last 3
- [ ] must_change_password=1, temporary_password_expires_at now+7d, forced onboarding first_name/last_name + change password
- [ ] Temp password 12 chars `Str::random(12)` with upper lower number special via custom generator, not simple
- [ ] Login rate limiting 5 per 15 min per IP and per username via Laravel throttle middleware file cache driver, returns 429 with Retry-After header, logs AuditLog failed_login IP UA
- [ ] Banned user check before password verify: is_banned true -> 403 with banned_reason, not 401
- [ ] Sanctum httpOnly secure SameSite Strict cookie + CSRF token X-XSRF-TOKEN double submit, not JWT in localStorage
- [ ] Refresh? Sanctum token expiration 7 days, revocation on password reset (delete personal_access_tokens), reuse detection? For Sanctum no reuse detection needed, but for JWT (if using tymon/jwt-auth) implement reuse detection: if old revoked token reused -> revoke all tokens for user
- [ ] Envelope PDF generated on-fly via dompdf + QR simple-qrcode, not stored on disk, contains username temp large monospace, QR, instructions Persian, printed date, operator name, warning 7 days, A5 printable

## RBAC & Authorization

- [ ] Flat permission matrix enforced via `RoleMiddleware` + Policies row-level dept scope: Expert can only CRUD own dept (WHERE department_id = user.department_id), Professor can only view own specs (WHERE professor_id = user.id), Admin can view all but not audit logs, Owner can view all read-only except user management + password reset
- [ ] Client never trusts local role for authz: Frontend Zustand authStore role only for UI hide, every API request server validates role via middleware, try to edit IndexedDB authStore role to admin then POST /api/v1/admin/semesters -> must 403
- [ ] Targeted messaging anti-enumeration: Generic error "کاربر یافت نشد یا دسترسی ندارید" for not found or not in dept or banned, not different messages
- [ ] Banned users: Only Admin/Owner can message banned, Expert cannot, check in MessageController

## File Upload Security

- [ ] Magic bytes check via finfo `finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file)` not extension, PDF must be `application/pdf` (%PDF magic), DOCX must be `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (PK zip header + [Content_Types].xml)
- [ ] Max sizes enforced server: Resource 50MB, Ticket image 5MB each max 3, Assignment 20MB, Form 20MB, Logo 2MB PNG/SVG, all validated via Laravel validation `max:51200` etc.
- [ ] Quota 5/day for student resource upload via MySQL table `resource_upload_counts` user_id date count, not Redis, atomic increment
- [ ] Block exe, bat, sh, dll, so, dylib via blacklist mime: `application/x-msdownload`, `application/x-sh`, etc.
- [ ] Path traversal prevention: Filename sanitized UUID only, not user input filename, store as `{uuid}.pdf` not original name, file_path not user controllable, use `Storage::disk('public')->putFileAs()`
- [ ] File path not directly accessible via URL? In shared host public_html/uploads is directly accessible, but need .htaccess deny execution of PHP files in uploads folder: Create `/public_html/uploads/.htaccess` with `php_flag engine off` + `Deny from all` for php files
- [ ] ClamAV optional scan via cron: If host has clamav, run `clamscan` on uploaded temp files, quarantine if virus

## External Links & XSS

- [ ] Telegram links open external browser with confirmation dialog "آیا می‌خواهید به گروه تلگرام {course} بروید؟" + shows URL, not WebView, to prevent XSS
- [ ] Form download via direct file `/uploads/forms/...` with Content-Disposition attachment, not inline
- [ ] All user inputs sanitized: supplementary_details, ticket description, message body, etc. via `e()` Blade escaping or `htmlspecialchars` if raw PHP, no raw HTML allowed except maybe NoticeBoard content which should be purified via HTMLPurifier
- [ ] SVG logo sanitized: Remove script tags via `HTMLPurifier` or `enshrined/svg-sanitize`, check for `<script>` and `onload=` attributes, remove

## AuditLog Encryption

- [ ] details JSON encrypted via `Crypt::encryptString` with `APP_KEY` 32 chars from .env, not plain JSON
- [ ] Key from env `AUDIT_ENCRYPTION_KEY` or `APP_KEY`, never logged, never committed to Git
- [ ] Even DB admin cannot read details without APP_KEY, test decryption fails with wrong key shows "خطا در رمزگشایی"
- [ ] AuditLog write failures should not block main request, log error to laravel.log, retry via queued job database driver
- [ ] Decryption only via `Crypt::decryptString` in AuditLogController with authorization Owner only

## Intranet Compliance Security

- [ ] No foreign dependency for critical: Pushe API key env, Kavenegar API key env, both Iranian servers, not Firebase/FCM for intranet must-have
- [ ] Polling endpoint `GET /api/notifications/unread` does not require external internet, works via internal IP 10.10.0.5 or same domain via internal DNS override
- [ ] SSL: Let's Encrypt for public domain, cert valid 90 days, renewal fails during shutdown but cert remains valid 90 days acceptable, internal same cert works via split DNS same domain, no self-signed needed for MVP
- [ ] Internal health endpoint `/api/v1/health` returns status ok, mode online/intranet/offline, version, timestamp, not sensitive info

## Rate Limiting (No Redis, File Cache)

- [ ] Login 5/15min per IP + per username via throttle middleware file cache driver
- [ ] Targeted messaging 10/min per expert, 20/min admin, 20/min owner via throttle middleware custom
- [ ] Broadcast 1/10min per professor per spec via MySQL table `broadcast_throttle` spec_id professor_id last_sent_at
- [ ] Ticket create 5/day per student via MySQL table `ticket_daily_counts`
- [ ] Resource upload 5/day per student via `resource_upload_counts`
- [ ] Excel import 1/10min per user via file cache
- [ ] Password reset 3/hour per operator via file cache

## Idempotency (No Redis, MySQL)

- [ ] Every mutating endpoint requires Idempotency-Key header UUID v4, server checks MySQL table `IdempotencyKeys` key UNIQUE, user_id, expires_at 24h, if exists returns previous response_code and response_body without reprocessing, else processes and stores
- [ ] Client generates UUID v4 via `idempotency.ts` generate, stores in IndexedDB syncQueue, sends as header, server stores in MySQL
- [ ] Key collision extremely unlikely UUID v4, but check unique constraint handling

## CSRF & Session

- [ ] Sanctum SPA cookie httpOnly secure SameSite Strict, CSRF token via `X-XSRF-TOKEN` header double submit, every POST/PATCH/DELETE requires CSRF token from cookie `XSRF-TOKEN`
- [ ] Staff session max 12h inactivity logout via `SESSION_LIFETIME=720` (12h), config `session.lifetime`
- [ ] DeviceToken table is_active false when token expired, last_used_at, for Pushe

## LRU Cleanup & Disk Limit (10GB Shop Plan)

- [ ] Cron daily `files:lru-cleanup` checks /uploads/resources size >8GB (80% of 10GB), deletes least recently downloaded non-protected (is_protected false, badge professor is protected) until size <7GB, logs AuditLog
- [ ] Protected professor badge files never auto-evicted, user pinned files never evicted (is_pinned true from FileCacheMeta IndexedDB? Actually server side pinned via Notification? For V9 shared host, pinned is client only, server side protected only professor badge)

## Error Handling (See 13_ERROR_HANDLING.md)

- [ ] 401 generic message to avoid enumeration for login: "نام کاربری یا رمز اشتباه است" not "User not found"
- [ ] 403 with Persian message and reason for banned, expired temp, etc.
- [ ] 422 with field errors Persian under field

## Final Security Scan

- [ ] Run OWASP ZAP scan against https://api.unify-cs.ac.ir - check XSS, SQL injection, file upload bypass (exe renamed pdf blocked via finfo), no critical vulnerabilities
- [ ] Test JWT tampering: Modify Sanctum token payload role student -> professor, request Admin endpoint, must 403
- [ ] Test local role escalation: Edit IndexedDB authStore role to admin, POST /api/v1/admin/semesters -> must 403 server never trusts local
- [ ] Test path traversal: Try to upload file with name `../../.env` -> must be sanitized to UUID, not overwrite .env

If all above checked, security DONE.

END SECURITY CHECKLIST
