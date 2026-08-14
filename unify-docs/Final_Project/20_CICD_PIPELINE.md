# 20 - CI/CD Pipeline - V9 Shared Host - GitHub Actions + cPanel FTP

## Purpose
Automate deployment to Pars Pack Cloud Host + Host Iran shared hosting without manual FTP upload. For agentic LLM to set up CI/CD.

## Problem with Shared Host

- No Docker, no SSH root, no GitHub Actions runner on host, only cPanel File Manager + FTP + Cron + Terminal limited
- Cannot run `docker-compose` or `k8s`, only FTP upload and `php artisan migrate` via Terminal

## Solution: GitHub Actions + FTP Deploy

### 1. GitHub Repo Structure

```
unify-project/
  .github/
    workflows/
      deploy.yml (CI/CD pipeline)
  backend/ (Laravel)
    app/
    routes/api.php
    database/migrations/
    .env.example.backend
    composer.json
  frontend/ (React PWA)
    src/
    public/
    .env.example.frontend
    vite.config.ts
    package.json
  Final_Project/ (docs - not deployed)
```

### 2. GitHub Secrets (Settings -> Secrets and variables -> Actions)

- `FTP_SERVER`: Cloud Host FTP server IP or hostname, e.g., `185.10.75.10` or `ftp.unify-cs.ac.ir` (from cPanel -> FTP Accounts -> FTP Server)
- `FTP_USERNAME`: cPanel username or FTP user e.g., `username@unify-cs.ac.ir`
- `FTP_PASSWORD`: cPanel password or FTP password
- `FTP_SERVER_DIR`: `/public_html/` for frontend + `/home/username/unify-backend/` for backend? Actually FTP root is `/home/username/`, so set `/public_html/` for frontend and handle backend separately via second FTP? Better use two secrets: `FTP_BACKEND_DIR` and `FTP_FRONTEND_DIR`
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for testing? Not needed for deploy, only for .env on server already set

### 3. GitHub Actions Workflow `.github/workflows/deploy.yml`

```yaml
name: Deploy to Pars Pack Cloud Host

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  build-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 18
      - name: Install frontend deps
        run: cd frontend && npm ci
      - name: Build frontend
        run: cd frontend && npm run build
        env:
          VITE_API_URL: https://api.unify-cs.ac.ir/api/v1
          VITE_APP_NAME: Unify
          VITE_POLLING_INTERVAL: 30000
      - name: Deploy frontend via FTP to Host Iran (or Cloud Host public_html)
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.FTP_SERVER_FRONTEND }}
          username: ${{ secrets.FTP_USERNAME_FRONTEND }}
          password: ${{ secrets.FTP_PASSWORD_FRONTEND }}
          local-dir: ./frontend/dist/
          server-dir: /public_html/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**

  deploy-backend:
    runs-on: ubuntu-latest
    needs: build-frontend
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install backend deps
        run: cd backend && composer install --no-dev --optimize-autoloader --no-interaction
      - name: Deploy backend via FTP to Cloud Host outside public_html
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
          local-dir: ./backend/
          server-dir: /home/username/unify-backend/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/storage/logs/**
            **/.env
            **/vendor/

  migrate:
    runs-on: ubuntu-latest
    needs: deploy-backend
    steps:
      - name: Run migrations via SSH (cPanel Terminal)
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USERNAME }}
          password: ${{ secrets.SSH_PASSWORD }}
          port: 22
          script: |
            cd /home/username/unify-backend
            /opt/cpanel/ea-php82/root/usr/bin/php artisan migrate --force
            /opt/cpanel/ea-php82/root/usr/bin/php artisan config:cache
            /opt/cpanel/ea-php82/root/usr/bin/php artisan route:cache
            /opt/cpanel/ea-php82/root/usr/bin/php artisan view:cache
```

### 4. Alternative Without SSH (If cPanel SSH Disabled)

If Pars Pack Cloud Host does not allow SSH (some shared hosts disable SSH), use cPanel Cron to run migrations:

- Create file `public_html/run-migrations.php` with:
```php
<?php
// Protected by secret key
if ($_GET['key'] !== 'your_secret_key') die('Forbidden');
exec('cd /home/username/unify-backend && /opt/cpanel/ea-php82/root/usr/bin/php artisan migrate --force 2>&1', $output);
echo implode("\n", $output);
```
- Then in GitHub Actions after FTP deploy, call via curl: `curl https://unify-cs.ac.ir/run-migrations.php?key=your_secret_key`
- Delete file after migration or protect with IP whitelist.

### 5. Database Migrations Safety

- Always run `php artisan migrate --force` not `migrate:fresh` on production (fresh would delete all data)
- For critical migrations like ENUM to VARCHAR (M1 fix), test on staging subdomain first `staging.unify-cs.ac.ir` with copy of production DB

### 6. Rollback Plan

- GitHub Actions deploys overwrite files, no built-in rollback
- Keep previous build as `public_html_backup_YYYYMMDD/` via FTP before deploy, or use cPanel File Manager backup full
- For DB rollback, before migrate, dump DB via `mysqldump` to `/home/username/backups/db_YYYYMMDD.sql.gz` via cron daily, so if migration fails, restore from backup

### 7. Environment Variables

- `.env` on server is NOT overwritten by FTP deploy (excluded in FTP-Deploy-Action exclude list)
- If you need to update .env, manually edit via cPanel File Manager, then run `php artisan config:cache` via SSH or cron

### 8. Frontend PWA Cache Busting

- Vite build generates hashed assets `assets/index-abc123.js`, so browser cache busting automatic
- Service Worker Workbox runtime caching for API GET specs 5min, but need to bust on new deploy: In `vite.config.ts` PWA plugin, set `workbox.runtimeCaching` with `expiration maxAgeSeconds 300` 5min, and on new deploy, increment `CACHE_VERSION` in `sw.js`

### 9. Cost

- GitHub Actions free for public repo, 2000 minutes/month for private repo free, enough for 600 students project
- No extra cost, just FTP credentials from cPanel

END CI/CD PIPELINE
