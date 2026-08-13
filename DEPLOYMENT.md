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
- [ ] All migrations ran successfully (`php artisan migrate:status` — 20 batches incl. integrity hardening)
- [ ] Owner user created (990000001)
- [ ] Backend `.env` built from `.env.example.backend` — incl. `APP_KEY`,
      fresh `DB_PASSWORD`, and `CORS_ALLOWED_ORIGINS` = the real frontend
      origin (without it the browser blocks every SPA→API call cross-subdomain)
- [ ] Frontend bundle was built with `VITE_API_URL=https://api.<domain>/api/v1`
      (deploy.yml sets it; a hand-built `dist/` without it calls same-origin
      /api/v1 → SPA fallback → HTML — looks like a total outage)
- [ ] cPanel cron entry live (§4) — grace-wipe / ticket escalation / backups
      / sanctum prune all depend on it
- [ ] SSL issued for BOTH `unify-cs.ac.ir` and `api.unify-cs.ac.ir` (§02)
- [ ] `php artisan storage:link` ran on the host (public disk → `/storage/...`) —
      form downloads + the branding logo 404 without it (V2-12)
- [ ] Polling works (30s)
- [ ] Push notifications: `PUSHE_ENABLED=false` by default — set `true` + `PUSHE_API_KEY` only if push is wanted
- [ ] 50GB storage limit set (`system_configs.storage_used_bytes` monitored at `/monitoring/storage`)

## 6. CI deploy (deploy.yml — the single pipeline)
- Trigger: push to `main` (or manual `workflow_dispatch`)
- Auth: SSH key — GitHub secrets `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, `SSH_HOST_KEY_FINGERPRINT`
- Flow: build frontend → scp release bundle → stage on server → atomic swap →
  `php artisan migrate --force` → `config/route/view:cache` → `/health` check →
  auto-rollback to previous release on failure (keeps last 5 releases)
- `ftp`-based deployment was removed; do not re-add FTP secrets

**Domain**: unify-cs.ac.ir
**API**: api.unify-cs.ac.ir

Ready for 600 students.
