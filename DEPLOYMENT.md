# Unify V9 - Production Deployment Guide (Pars Pack Cloud Host)

## 1. Prepare Backend
```bash
cd unify-backend
composer install --no-dev --optimize-autoloader
cp .env.example.backend .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=OwnerSeeder
php artisan storage:link
```

## 2. Frontend Build
```bash
cd frontend
npm install
npm run build
```

## 3. Upload to cPanel
- `unify-backend/` → `/home/username/unify-backend`
- `frontend/dist/*` → `/home/username/public_html`

## 4. Cron Jobs (cPanel)
```
*/5 * * * * cd /home/username/unify-backend && /usr/local/bin/php artisan schedule:run
```

## 5. Final Checklist
- [ ] All 14 migrations ran successfully
- [ ] Owner user created (990000001)
- [ ] `.htaccess` in uploads folder exists
- [ ] Polling works (30s)
- [ ] Pushe API key configured
- [ ] 50GB storage limit set

**Domain**: unify-cs.ac.ir
**API**: api.unify-cs.ac.ir

Ready for 600 students.
