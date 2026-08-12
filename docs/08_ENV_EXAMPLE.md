# 08 - ENV Example - V9 Shared Host

This doc contains `.env.example` for backend Laravel and frontend React, plus explanation.

## Backend Laravel `.env.example` - For Pars Pack Cloud Host

Copy this to `.env` in `unify-backend/` and fill.

```env
APP_NAME=Unify
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_FROM_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://unify-cs.ac.ir
FRONTEND_URL=https://unify-cs.ac.ir
API_URL=https://api.unify-cs.ac.ir

# Database - MySQL on Cloud Host cPanel
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=username_unify
DB_USERNAME=username_dbuser
DB_PASSWORD=your_strong_password

# Cache - File driver (no Redis on shared host) + Memcached optional if Cloud Host provides
CACHE_DRIVER=file
# If Cloud Host has Memcached enabled, you can switch to:
# CACHE_DRIVER=memcached
# MEMCACHED_HOST=127.0.0.1
# MEMCACHED_PORT=11211

QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=720

FILESYSTEM_DISK=public
# For uploads, storage/app/public linked to public_html/uploads via php artisan storage:link

# Security
PASSWORD_PEPPER=your_random_32_chars_pepper_from_env
AUDIT_ENCRYPTION_KEY=your_audit_key_32_chars
BCRYPT_ROUNDS=12
HASH_DRIVER=argon2id

# Sanctum SPA
SANCTUM_STATEFUL_DOMAINS=unify-cs.ac.ir,api.unify-cs.ac.ir,localhost:5173
SESSION_DOMAIN=.unify-cs.ac.ir

# Push - Pushe (Iranian push for Android intranet)
PUSHE_API_KEY=your_pushe_api_key_from_pushe.co_dashboard
PUSHE_APP_ID=your_pushe_app_id

# SMS Fallback - Kavenegar (optional for critical alerts)
KAVENEGAR_API_KEY=your_kavenegar_api_key
KAVENEGAR_SENDER=10004346

# Branding
BRAND_NAME=Unify
LOGO_PATH=/uploads/branding/logo.png

# File Upload Limits (must match php.ini on cPanel)
MAX_FILE_SIZE_MB=50
STUDENT_DAILY_UPLOAD_LIMIT=5
TICKET_IMAGE_MAX_MB=5
ASSIGNMENT_MAX_MB=20

# Rate Limiting
LOGIN_THROTTLE_MAX_ATTEMPTS=5
LOGIN_THROTTLE_DECAY_MINUTES=15
BROADCAST_THROTTLE_PER_10MIN=1
TARGETED_MESSAGE_PER_MIN=10

# Cron / Grace Period
GRACE_PERIOD_HOURS=24
TICKET_ESCALATION_HOURS=48

# Shamsi
TIMEZONE=Asia/Tehran
```

## Frontend React `.env.example` - PWA

Copy to `frontend/.env` and fill.

```env
VITE_API_URL=https://api.unify-cs.ac.ir/api/v1
VITE_APP_NAME=Unify
VITE_APP_URL=https://unify-cs.ac.ir
VITE_POLLING_INTERVAL=15000
VITE_POLLING_BACKGROUND_INTERVAL=60000
VITE_ENABLE_PUSHE=true
VITE_PUSHE_APP_ID=your_pushe_app_id
VITE_ENABLE_SMS_OPT_IN=true
VITE_FILE_CACHE_MAX_MB=100
VITE_MAX_UPLOAD_MB=50

# For local dev
# VITE_API_URL=http://localhost:8000/api/v1
```

## How to Generate Keys

```bash
# Laravel APP_KEY
cd unify-backend && php artisan key:generate

# Pushe API Key
# Go to https://pushe.co -> Dashboard -> Apps -> Your App -> API Key

# Kavenegar API Key
# Go to https://kavenegar.com -> Panel -> API Key

# Password pepper and audit key - generate random 32 chars
php -r "echo bin2hex(random_bytes(16));"
```

## cPanel Specific Notes

- In cPanel -> Select PHP Version -> Set PHP 8.2 + enable extensions: `nd_pdo_mysql, curl, mbstring, openssl, fileinfo, gd, zip, intl`
- In cPanel -> MySQL Databases -> Create DB + User + Add User to DB All Privileges
- In cPanel -> Cron Jobs -> Add: `* * * * * cd /home/username/unify-backend && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1` - Note PHP path may be different, check cPanel -> Select PHP Version -> shows path, usually `/opt/cpanel/ea-php82/root/usr/bin/php`
- For storage link: SSH or cPanel Terminal: `cd /home/username/unify-backend && php artisan storage:link` - If fails due to symlink restriction, manually create symlink via File Manager or ask support to allow symlink, alternative: set `FILESYSTEM_DISK=public` but change `filesystems.php` public disk root to `/home/username/public_html/uploads`
- For .env permissions: `chmod 600 .env`

## Security Notes for .env

- Never commit .env to Git, only .env.example
- .env contains APP_KEY which encrypts AuditLog details via Crypt::encryptString - if lost, audit logs cannot be decrypted
- PUSHE_API_KEY and KAVENEGAR_API_KEY are secrets, store in env only
- For envelope PDFs dompdf uses storage/app/temp - ensure that folder not publicly accessible via .htaccess deny

END ENV EXAMPLE
