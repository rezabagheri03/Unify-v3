# Deployment Guide - Pars Pack Cloud Host + Host Iran - V9 FIXED - 50GB Evergreen

## Prerequisites
- Domain .ir from IRNIC, e.g., unify-cs.ac.ir
- Pars Pack account
- Local dev: PHP 8.2 + Composer + Node 18 + MySQL 8

## Step 1: Buy Hosting - FIXED for Evergreen 50GB

### For 600 Students - Recommended FIXED per User Choice upgrade_50gb

**Problem with original 10GB:** Evergreen Resource Hub permanent + 600 students * 5 files * 3MB = 9GB + ticket images 4.8GB + assignments 6GB = ~20GB per semester, 10GB fills in 1 semester, LRU deletes old notes breaking evergreen promise.

**Fix:** Buy Cloud Host Shop + 40GB extra block storage add-on via Pars Pack ticket.

- Go to https://parspack.com/host/cloud-host
- Select location **Iran**
- Select plan **فروشگاهی: 10GB SSD / 5 vCPU / 7GB RAM / Unlimited Bandwidth** - 588k-716,550 Toman/month
- After purchase, open Ticket to Pars Pack Support: "لطفا ۴۰ گیگابایت فضای اضافه Block Storage به سرور ابری من اضافه کنید - برای پروژه دانشگاهی Evergreen نیاز به ۵۰ گیگ دارم"
- Cost extra block storage: ~300k-500k Toman/month, total ~1.2-1.5M Toman/month for 50GB total (10GB base + 40GB extra) - truly evergreen for 4 years (60GB needed for 4 years, 50GB enough for 2-3 years + LRU)
- Alternative cheap: Keep 10GB hot + archive old to Arvan S3 cold storage via rclone (cost ~200k/month for 100GB cold)

For MVP cheap start: **استارتاپ: 5GB / 3 vCPU / 4GB RAM / 341,600 Toman** but you will need to upgrade after 1 semester.

Control panel cPanel (or DirectAdmin), period monthly, upgrade zero downtime in panel.

### Optional Second Host for Frontend (User wanted both links)
- Go to https://parspack.com/host/iran
- Buy **Host Iran** cheapest plan for frontend static only (low ping, half-price traffic)
- Point domain `unify-cs.ac.ir` -> Host Iran IP (frontend)
- Point subdomain `api.unify-cs.ac.ir` -> Cloud Host IP (backend)

For simplicity, this guide assumes SINGLE Cloud Host Shop + 40GB extra = 50GB total for both frontend + backend.

## Step 2: Domain Setup - Split DNS for Intranet
1. In IRNIC nic.ir, set DNS to Pars Pack nameservers (given after purchase, e.g., ns1.parspack.com)
2. In Pars Pack panel -> cPanel -> Zone Editor -> Add A record `unify-cs.ac.ir` -> Cloud Host public IP (e.g., 185.10.75.10)
3. If using two hosts: Add A record `api` -> Cloud Host IP, A record `@` and `www` -> Host Iran IP
4. **For intranet:** In university internal DNS server or via /etc/hosts on lab PCs, override same domain `unify-cs.ac.ir` -> private IP 10.10.0.5 (if university gives you private IP). This is split DNS: same domain, public IP outside, private IP inside, so same SSL cert works both intranet and internet (FIX H7 self-signed fallback alternative).

## Step 3: Create Database
1. cPanel -> MySQL Databases -> Create database `username_unify` 
2. Create user, add to database All Privileges
3. Note credentials for .env

## Step 4: Upload Backend Laravel

### Local Prepare
```bash
composer create-project laravel/laravel unify-backend
cd unify-backend
composer require morilog/jalali phpoffice/phpspreadsheet intervention/image simplesoftwareio/simple-qrcode barryvdh/laravel-dompdf guzzlehttp/guzzle
# Copy V9 app code from repo (app/, routes/api.php, database/migrations V9 - 13 migrations including fixes C1-C2-H2-H3-H5-H9)
cp -r /path/to/unify-backend/app ./app
cp -r /path/to/database/migrations ./database
cp .env.example.backend .env
# Edit .env:
# APP_NAME=Unify
# APP_ENV=production
# APP_KEY= (php artisan key:generate)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=username_unify
# DB_USERNAME=username_dbuser
# DB_PASSWORD=yourpass
# FILESYSTEM_DISK=public
# PUSHE_API_KEY=your_pushe_key (from pushe.co)
# KAVENEGAR_API_KEY=optional
```

### Upload
1. Zip backend folder excluding vendor, node_modules: `zip -r backend.zip app routes database config .env.example.backend` (don't include .env with secrets)
2. cPanel File Manager -> Go to `/home/username/` (one level above public_html) -> Upload `backend.zip` -> Extract to `unify-backend`
3. SSH (cPanel -> Terminal) -> `cd /home/username/unify-backend && composer install --no-dev --optimize-autoloader`
4. `cp .env.example.backend .env` then edit .env via File Manager nano set DB credentials, APP_KEY etc., `php artisan key:generate`
5. `php artisan migrate --force` (runs 13 migrations including fixes: family_id nullable, academic_status_at_enrollment, academic_status_history, broadcast_throttles, download_daily_counts, resource_download_logs, storage_stats, is_next_day flag for overnight)
6. `php artisan storage:link` -> creates symlink `/home/username/unify-backend/storage/app/public` to `/home/username/public_html/uploads`? If fails due to symlink restriction on shared host, fallback: In `config/filesystems.php` change public disk root to `/home/username/public_html/uploads` directly, no symlink needed.

### .htaccess for API + Security FIX H4

In `/home/username/public_html/.htaccess` for Laravel public handling + SPA fallback + **security for uploads folder**:

Main `.htaccess` for SPA:

```
<IfModule mod_rewrite.c>
RewriteEngine On
# Exclude /api/
RewriteCond %{REQUEST_URI} !^/api/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.html [L]
</IfModule>
```

Create `/home/username/public_html/uploads/.htaccess` (FIX H4 - Prevent PHP execution in uploads):

```
# FIX H4: Prevent PHP execution in uploads to block shell.pdf.php attack
php_flag engine off
RemoveHandler .php .phtml .php3 .php4 .php5
Deny from all
<FilesMatch "\.(pdf|docx|png|jpg|jpeg)$">
  Allow from all
</FilesMatch>
```

Also add validation in Laravel Request: Reject filename containing `.php` anywhere, check double extension `file.pdf.php` via regex.

### Subdomain for API (if using single host, still recommended to avoid SPA conflict)

Create subdomain `api.unify-cs.ac.ir` in cPanel -> Subdomains -> Create `api` -> Document Root `/home/username/unify-backend/public` (Laravel public). Then frontend calls `https://api.unify-cs.ac.ir/api/v1/...`

## Step 5: Upload Frontend React PWA

Local:
```bash
cd /path/to/frontend
npm install
npm run build
# Creates dist/
```

Upload:
1. cPanel File Manager -> public_html (if using two hosts, this is Host Iran's public_html, if single host Cloud Host's public_html)
2. Upload dist/* files (index.html, assets folder)
3. Ensure main .htaccess SPA fallback exists as above

## Step 6: Cron Jobs - FIXED per User Choice lazy_check + every 5 min

**Problem C3:** Many shared hosts limit cron minimum to every 15 min on Starter, not every minute.

**Fix per your choice: lazy_check + every 5 min**

cPanel -> Cron Jobs -> Add (choose every 5 minutes, not every minute):

```
*/5 * * * * cd /home/username/unify-backend && /opt/cpanel/ea-php82/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Note: PHP path may be `/opt/cpanel/ea-php82/root/usr/bin/php` or `/usr/bin/php`, check cPanel -> Select PHP Version -> shows path.

In Laravel `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule) {
  $schedule->command('enrollments:wipe-grace')->everyFiveMinutes(); // FIX C3: was everyMinute, now everyFiveMinutes + lazy check fallback
  $schedule->command('tickets:escalate')->hourly();
  $schedule->command('calendar:warn')->dailyAt('08:00');
  $schedule->command('resources:cleanup-old-versions')->dailyAt('03:00');
  $schedule->command('files:lru-cleanup')->dailyAt('04:00'); // FIX C4: LRU cleanup for 50GB limit: deletes least recently downloaded non-protected (is_protected=0) only if size >40GB (80% of 50GB) until <35GB
  $schedule->command('idempotency:cleanup')->dailyAt('02:00'); // FIX H1: cleanup expired idempotency keys older than 24h
  $schedule->command('storage:calculate-stats')->dailyAt('01:00'); // for monitoring 50GB usage
}

protected function commands() {
  // Lazy check fallback for grace period: On every enrollment finalize request, also check if grace_period_ends_at <= now and handled=0, then run wipe immediately
}
```

Create those commands via `php artisan make:command`.

**Lazy check fallback implementation (in EnrollmentController@final):**
```php
$semester = Semester::where('is_current', true)->first();
if ($semester && $semester->grace_period_ends_at && $semester->grace_period_ends_at <= now() && !$semester->grace_period_handled) {
  Artisan::call('enrollments:wipe-grace');
}
```

## Step 7: SSL - FIXED H7 Let's Encrypt renewal fails during shutdown

cPanel -> Let's Encrypt SSL -> Issue for `unify-cs.ac.ir` and `api.unify-cs.ac.ir` and `www`. Auto renew.

**Problem H7:** Let's Encrypt HTTP-01 challenge needs outside internet to validate domain. During national shutdown (days/weeks), renewal fails, cert remains valid 90 days okay for short shutdown, but if shutdown >90 days or cert expires right during shutdown, site shows SSL error on intranet.

**Fix:** Generate self-signed fallback cert for `unify.local` internal domain + add to Android network_security_config.xml to trust self-signed.

Steps:
1. Generate self-signed: `openssl req -x509 -nodes -days 3650 -newkey rsa:4096 -keyout /home/username/unify-backend/storage/app/unify.local.key -out /home/username/unify-backend/storage/app/unify.local.crt -subj "/C=IR/ST=Tehran/O=University/CN=unify.local"`
2. Upload `unify.local.crt` and `.key` via cPanel -> SSL/TLS -> Manage SSL Sites -> Install for `unify.local` (if you have internal DNS)
3. For Android: In `android/app/src/main/res/xml/network_security_config.xml` add trust for self-signed:
```xml
<?xml version="1.0" encoding="utf-8"?>
<network-security-config>
  <domain-config cleartextTrafficPermitted="false">
    <domain includeSubdomains="true">unify-cs.ac.ir</domain>
    <domain includeSubdomains="true">unify.local</domain>
    <trust-anchors>
      <certificates src="system" />
      <certificates src="@raw/unify_local" />
    </trust-anchors>
  </domain-config>
</network-security-config>
```
4. Put `unify.local.crt` file in `android/app/src/main/res/raw/unify_local.pem`

For PWA on intranet with expired Let's Encrypt, students can still access via `https://unify.local` with self-signed trusted by app, or via `http://10.10.0.5` if you allow HTTP for intranet fallback (not recommended, but as last resort).

## Step 8: Pushe Setup + Intranet Test FIX H8

1. Go to pushe.co, register, create app Android, get API key and App ID
2. In Laravel .env set `PUSHE_API_KEY=`
3. Create service `App\Services\PusheService` that calls `https://api.pushe.co/v2/...` via Guzzle HTTP
4. When notification event, call PusheService::send($userIds, $title, $body, $data)
5. Frontend Android Capacitor app registers token via `POST /api/devices` with provider pushe

**FIX H8: Test intranet reachability before production:**
On university lab PC connected to intranet WiFi with international gateway blocked (ask IT to block 8.8.8.8 temporarily or use firewall), run:
```bash
curl -v https://api.pushe.co
nslookup api.pushe.co
# Check IP is Iranian e.g., 185.10.x.x
# Check curl succeeds (HTTP 200 or 401 auth error but not timeout)
```
If curl timeout or IP foreign (e.g., Cloudflare 104.x.x.x), ask Pushe support for intranet IP endpoint or use alternative Najva.

Document test result in `10_TESTING_STRATEGY.md` intranet simulation.

## Step 9: Offsite Backup FIX H6

**Problem:** cPanel backup on same disk, if disk fails data lost.

**Fix:** Use rclone to second Iranian provider (e.g., Arvan S3 or Pars Pack S3 or second VPS in different DC).

cPanel -> Cron Jobs -> Add daily 03:30:

```
30 3 * * * /usr/bin/mysqldump -u username_dbuser -p'yourpass' username_unify | gzip > /home/username/backups/db_$(date +\%Y\%m\%d).sql.gz && /home/username/rclone/rclone copy /home/username/backups/ arvan-backup:unify-backups/ --config /home/username/rclone/rclone.conf >> /home/username/backups/rclone.log 2>&1
```

Setup rclone: SSH -> `curl https://rclone.org/install.sh | sudo bash` (if sudo not allowed, download binary to ~/rclone), `~/rclone/rclone config` create Arvan S3 remote.

Cost: Arvan S3 100GB cold ~200k Toman/month, backup VPS second DC 2M/month.

## Step 10: Test + Polling Interval Fix C5

Open https://unify-cs.ac.ir -> login

**FIX C5: Polling interval changed from 15s/60s to 30s/120s + file cache 5s per user to reduce MySQL max_connections load**

In frontend `.env`:
```
VITE_POLLING_INTERVAL=30000 (was 15000)
VITE_POLLING_BACKGROUND_INTERVAL=120000 (was 60000)
```

In backend `NotificationController@unread`:
```php
$cacheKey = "notifications:{$user->id}:{$since}";
return Cache::remember($cacheKey, 5, function() use ($user, $since) {
  return Notification::where('user_id', $user->id)->where('read', false)->where('created_at', '>', $since)->get();
});
```
File cache 5s per user reduces DB hits: 600 users * 1 req/30s = 20 req/s average, with 5s cache hit rate 80% reduces to ~4 req/s DB queries.

Test DevTools Network tab should see polling GET /api/notifications/unread every 30s foreground.

## Step 11: Android APK (Optional)

Local: `npx cap add android`, edit `capacitor.config.json` server.url = https://unify-cs.ac.ir
Build: `npm run build && npx cap copy && npx cap open android` -> Android Studio -> Build APK signed with keystore
Upload APK to `public_html/app.apk`
Add QR code on login page linking to `https://unify-cs.ac.ir/app.apk`
Include network_security_config.xml for self-signed fallback FIX H7

## Scaling Notes for 600 Students on 50GB

- If CPU 100% red in cPanel metrics, upgrade from Startup 4GB to Shop 7GB RAM in Pars Pack panel one click zero downtime. Shop 10GB base + 40GB extra block storage = 50GB total recommended for evergreen 4 years (C4 fix).
- If disk 80% full 40GB of 50GB, LRU cleanup cron daily deletes least recently downloaded non-protected until <35GB, protected professor badge files never deleted (is_protected=1)
- Download daily limit: Max 20 downloads per student per day via `download_daily_counts` table to prevent fair usage 2TB exceed (FIX H5)
- Unlimited bandwidth is marketing fair usage ~2TB/month, 600 students * 10 resources *5MB=30GB/day = 900GB/month okay, but 50 resources*50MB=1.5TB/day=45TB/month will suspend, so implement rate limiting

## Cost for 600 Students V9 FIXED

- Cloud Host Shop 10GB base + 40GB extra block storage = 50GB total: ~1.2-1.5M Toman/month (716k base + 300-500k extra)
- Domain .ir: ~50k Toman/year
- Pushe: Free up to 10k devices, then ~500k/month
- Arvan S3 offsite backup 100GB: ~200k/month
- Total: ~1.5-2.2M/month vs VPS 8-13M, save ~80% but truly evergreen permanent not LRU 10GB

END DEPLOYMENT GUIDE FIXED
