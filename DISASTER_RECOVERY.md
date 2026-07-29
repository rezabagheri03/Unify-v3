# Disaster Recovery Plan - Unify V9

## 1. Backup Strategy

### Automated Backups (Recommended)
Add these cron jobs on Pars Pack:

```bash
# Database backup (daily at 02:00)
0 2 * * * cd /home/username/unify-backend && php artisan backup:database

# Files backup (daily at 03:00)
0 3 * * * cd /home/username/unify-backend && php artisan backup:files
```

### Manual Backup
```bash
# Database
mysqldump -uusername -ppassword unify_db > backup_$(date +%Y%m%d).sql

# Files
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

## 2. Recovery Procedures

### Scenario 1: Database Corruption / Loss

1. Restore from latest backup:
   ```bash
   mysql -uusername -ppassword unify_db < backup_20260101.sql
   ```

2. Run migrations if needed:
   ```bash
   php artisan migrate --force
   ```

### Scenario 2: File Storage Loss

1. Restore from file backup:
   ```bash
   tar -xzf uploads_backup_20260101.tar.gz -C storage/app/public/
   ```

2. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Scenario 3: Complete Server Failure

1. Redeploy from GitHub Actions
2. Restore latest database backup
3. Restore latest file backup
4. Run `php artisan storage:link`

## 3. Monitoring Recommendations

- Use **UptimeRobot** to monitor `https://api.unify-cs.ac.ir/api/health`
- Set alerts for:
  - Storage usage > 80%
  - Health endpoint returns non-200
  - Database connection failures

## 4. Recovery Time Objectives (RTO)

- Database: < 30 minutes
- Files: < 1 hour
- Full system: < 4 hours

## 5. Contact

- System Owner: [Your Name]
- Hosting Support: Pars Pack Ticket System

---

**Last Updated:** 2026-07-19