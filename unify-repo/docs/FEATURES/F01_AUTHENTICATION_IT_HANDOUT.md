# F01 Authentication & IT Handout - V9 Shared Host (Laravel)

## Purpose
Secure login with physical envelope, forced change, Argon2id, Sanctum, rate limit via MySQL cache, honor ack.

## Flows

### Bulk Creation (Owner)
Owner uploads Excel 2000 rows -> Laravel validates transactional via PhpSpreadsheet -> for each row generate temp 12 chars Str::random, hash Hash::make argon2id, must_change_password=1, temporary_password_expires_at now+7d, first_name/last_name from Excel but still require onboarding, create user, log AuditLog, generate envelopes ZIP dompdf + QR simple-qrcode.

### Login
POST /api/v1/auth/login {username, password} + IP
Rate limit: Laravel throttle middleware 5 per 15 min per IP and per username via file cache, 429 Retry-After
Lookup user by id, check is_banned -> 403 with banned_reason, check temporary_password_expires_at < now for must_change_password -> 403 "رمز موقت منقضی شده"
Verify Hash::check, if fails log AuditLog failed_login ip, user_agent, increment attempts, return 401 generic "نام کاربری یا رمز اشتباه است"
If success: reset attempts, update last_login_at, generate Sanctum token (personal access token) 7d expiry, cookie httpOnly secure, return token + user JSON + must_change_password flag, log login

### Onboarding Forced
If must_change_password or first_name null -> frontend routes to /onboarding
Form first_name, last_name required, old password temp, new password, confirm, complexity live min 8 upper lower number special not same temp not in last 3 PasswordHistory table keep last 3
POST /api/v1/onboarding {first_name, last_name, supplementary_details} + POST /api/v1/password/change
After success must_change_password=0, temporary_password_expires_at null, create PasswordHistory, revoke other tokens, notification "رمز تغییر کرد"

### Password Change Settings
POST /api/v1/password/change {old, new} verify old Hash::check, check not in last 3, hash new, store history, option "خروج از سایر دستگاه‌ها" checkbox default true -> revoke other tokens

### Forgot Physical (Owner)
POST /api/v1/owner/users/{id}/reset-password {reason} Owner role, generates new temp 12 chars, hash, must_change_password=1, expires 7d, revokes all tokens (Sanctum tokens delete), AuditLog password_reset is_suspicious if >2 per month, generates envelope PDF on-fly dompdf not stored, response PDF download

## Offline V9
Login requires online, after login cached user in IndexedDB for offline read banner "حالت آفلاین". Offline login checks local IndexedDB? No, require online for auth, but after first login cached data allows viewing dashboard offline.

## API
POST /api/v1/auth/login, POST /api/v1/auth/logout (revoke), POST /api/v1/onboarding, POST /api/v1/password/change, POST /api/v1/auth/validate (Sanctum)

## Security V9
Argon2id via PASSWORD_ARGON2ID, pepper env, PasswordHistory last 3, Refresh? Sanctum token 7d, httpOnly SameSite Strict + CSRF, Envelope PDF generated on-fly not stored, QR not logged
