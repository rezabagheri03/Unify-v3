# 22 - Disaster Recovery Plan - V9 Shared Host - 600 Students

## Purpose
What if entire Pars Pack Cloud Host DC down? Or disk fails? Or MySQL corrupted? Or university data center fire? For 600 students, need recovery plan.

## Scenarios

### Scenario 1: Cloud Host Disk Failure - MySQL Data Loss

**Probability:** Low (Pars Pack uses RAID, but still possible)

**Impact:** All enrollments, resources metadata, tickets, assignments lost. File storage /uploads/resources 10-50GB lost.

**Recovery Plan:**

1. **Daily DB Backup:** Cron daily 03:30 `mysqldump -u username_dbuser -p'pass' username_unify | gzip > /home/username/backups/db_YYYYMMDD.sql.gz` via cPanel Cron. Keep 7 daily backups on same disk (risk same disk) + offsite backup to Arvan S3 or second VPS via rclone (see deployment guide H6 fix).
2. **File Backup:** Daily `tar czf /home/username/backups/uploads_YYYYMMDD.tar.gz /home/username/public_html/uploads` + rclone copy to Arvan S3 `arvan-backup:unify-backups/`
3. **Restore Steps:**
   - Buy new Cloud Host from Pars Pack (or use backup VPS)
   - Create MySQL DB, import latest backup `gunzip < db_YYYYMMDD.sql.gz | mysql -u user -p db`
   - Upload /uploads folder from backup tar
   - Run `php artisan storage:link`
   - Change DNS A record to new host IP
   - Test login, enrollment, resource download
   - Notify students via Pushe + SMS "سایت بازیابی شد - ممکن است اطلاعات 24 ساعت گذشته از دست رفته باشد"

**RTO (Recovery Time Objective):** 4 hours (buy new host 1h + restore DB 1h + restore files 1h + DNS propagation 1h)
**RPO (Recovery Point Objective):** 24 hours (daily backup, worst case lose 24h data)

**Cost:** Arvan S3 100GB cold ~200k/month + backup VPS second DC 2M/month optional. For MVP 600 students, manual external HDD weekly backup to save cost: Every Friday, IT staff downloads backup via FTP to external HDD, keeps 4 weeks.

### Scenario 2: Pars Pack Entire DC Down (Tehran DC Fire, Power Outage)

**Probability:** Very low (Tier 3 DC), but possible during national incident

**Impact:** All services down, even intranet if DC is Tehran and university is in Tehran and uses same DC, intranet also down.

**Recovery Plan:**

1. **Secondary DC:** Have backup VPS in different city DC (e.g., Pars Pack has Tehran + Shiraz DC, or use Iranserver Shiraz DC). Keep daily offsite backup there.
2. **DNS Failover:** Use Cloudflare or ArvanCloud CDN with health check? But Cloudflare outside Iran may not work during national shutdown. Better: Manual DNS failover: IT staff changes DNS A record to backup VPS IP when primary DC down, TTL set to 300 seconds (5 min) for fast failover.
3. **Intranet Fallback:** If both primary and backup DC down and university has own lab PC, use lab PC as emergency server with latest backup from external HDD, IP 10.10.0.5 internal, students on campus WiFi can still access via `http://10.10.0.5` or `http://unify.local`

**RTO:** 1-4 hours manual DNS change
**RPO:** 24h

### Scenario 3: Accidental Hard Delete by Admin (Former Professor Notes Hard Delete)

**Probability:** Medium (Admin clicks hard delete by mistake)

**Impact:** Resource file content deleted immediately, metadata hard deleted, ratings lost.

**Recovery Plan:**

1. **Soft Delete First:** Even though spec says hard delete for former professor notes, we implemented soft deletes in migration 000014 for resources, courses, specs, etc. So hard delete actually sets deleted_at first, file content deleted after 30 days via cron `resources:cleanup-old-versions`, so within 30 days can restore from backup.
2. **AuditLog:** Every hard delete logs AuditLog deletion with resource_type, resource_id, user_id, IP, details JSON old values, is_suspicious false, so Owner can view who deleted.
3. **Restore Steps:** From backup tar `uploads_YYYYMMDD.tar.gz` extract specific file, restore row from AuditLog details JSON old values, set deleted_at null, file_path restored, is_deleted_content false.

**RTO:** 1 hour
**RPO:** 0 (if within 30 days, file still in backup)

### Scenario 4: National Internet Shutdown (International Gateway Closed, SHOMA Only)

**Probability:** Medium-High in Iran (happens during protests/exams)

**Impact:** External services like Let's Encrypt renewal, Google Fonts, CDN, FCM push, `api.pushe.co` if foreign IP, `8.8.8.8` check all fail. But intranet should still work if host inside Iran.

**Recovery Plan:**

1. **Polling not WebSocket:** Already fixed to polling every 30s foreground / 120s background, polling is HTTP to Iranian IP, works on SHOMA.
2. **Pushe:** Test Pushe API reachable during intranet (FIX H8). If `api.pushe.co` resolves to foreign Cloudflare IP and fails during shutdown, ask Pushe for intranet IP endpoint or fallback to polling only during intranet mode (detect via health check internal reachable but external not -> isIntranetMode true, disable Pushe, rely on polling + local notifications).
3. **Let's Encrypt:** Cert valid 90 days, renewal fails during shutdown but cert remains valid. For fallback, self-signed cert for `unify.local` internal domain + network_security_config.xml for Android to trust self-signed (FIX H7).
4. **Google Fonts:** Don't use Google Fonts (requires outside internet), use Vazirmatn font self-hosted in `/public/fonts/` on same host, so works during intranet.
5. **CDN:** Don't use Cloudflare CDN (outside), use Pars Pack LiteSpeed cache internal + self-hosted assets.

**RTO:** 0 (intranet should continue working)
**RPO:** 0

### Scenario 5: Student Data Breach (File Upload Shell)

**Probability:** Medium (attacker uploads shell.pdf.php with PDF magic but PHP code)

**Impact:** Full server compromise, all student data leaked, deface.

**Recovery Plan:**

1. **Prevention:** FIX H4 .htaccess `php_flag engine off` + `Deny from all` for php files in uploads, validation reject filename containing `.php` anywhere, magic bytes finfo check PDF %PDF and DOCX PK zip header + `[Content_Types].xml`, ClamAV optional scan.
2. **Detection:** AuditLog file_upload with user_id, file_mime, file_size, IP, is_suspicious if mime mismatch extension, daily cron scans uploads folder for php files `find /home/username/public_html/uploads -name "*.php" -type f` if found alert Owner + auto delete.
3. **Response:** If breach detected (e.g., `shell.pdf.php` found or AuditLog shows file_upload with is_suspicious true and IP foreign), immediately ban user (is_banned true), delete file, restore from backup if needed, force password reset for all users (must_change_password=1 via Owner bulk reset), notify Owner via Pushe critical + SMS.
4. **Restore:** From backup tar uploads before breach date.

**RTO:** 2 hours (ban + delete + restore)
**RPO:** 0 (if backup clean)

### Scenario 6: MySQL max_connections Exceeded During Enrollment Peak

**Probability:** High for 600 students with polling 30s + 200 concurrent finalizing

**Impact:** 500 errors "Too many connections", enrollment fails, students complain.

**Recovery Plan:**

1. **Prevention:** FIX C5 polling 30s/120s + file cache 5s per user, composite indexes, eager loading, stagger enrollment times via lottery (final year first hour, GPA_A second hour, normal third hour, conditional fourth hour) via Academic Calendar events.
2. **Immediate:** If MySQL max_connections hit, increase max_connections via cPanel -> MySQL -> Variables? On shared host may not allow, need to ask Pars Pack support to increase from 100 to 200.
3. **Fallback:** Enable maintenance mode `php artisan down --message="ثبت‌نام به دلیل ترافیک بالا موقتا متوقف شد - 10 دقیقه دیگر تلاش کنید" --retry=600` to temporarily block new requests, let existing finish.

**RTO:** 10 minutes (enable maintenance mode + increase max_connections)
**RPO:** 0

### Backup Checklist (Daily)

- [ ] Cron 03:30 mysqldump gzip to /home/username/backups/db_YYYYMMDD.sql.gz keep 7 days local + rclone copy to Arvan S3 or second VPS
- [ ] Cron 04:00 tar uploads to /home/username/backups/uploads_YYYYMMDD.tar.gz keep 7 days local + rclone copy to Arvan S3
- [ ] Cron daily storage:calculate-stats to monitor 50GB usage
- [ ] Manual weekly external HDD backup by IT staff via FTP download

### Restore Drill (Monthly)

- Once a month, IT staff restores backup to staging subdomain `staging.unify-cs.ac.ir` with copy of production DB + uploads, tests login, enrollment, resource download, ticketing, to ensure backup is valid.

### Cost for DR

- Arvan S3 100GB cold ~200k/month
- Backup VPS second DC (Shiraz) 2M/month optional for MVP, can be manual HDD weekly to save cost
- No extra cost for maintenance mode, file cache, polling interval increase

END DISASTER RECOVERY
