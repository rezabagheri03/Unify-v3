# Unify V9 - University Assistant System

**Full Production Project** for 600 Computer Engineering students  
**Target Hosting:** Pars Pack Cloud Host (Shop 10GB + 40GB extra = 50GB evergreen)

## Stack
- **Backend**: Laravel 10 + PHP 8.2 + MySQL 8
- **Frontend**: React 18 + Vite + MUI RTL + PWA (Workbox)
- **Real-time**: Polling 30s/120s + 5s file cache + Pushe PHP
- **Storage**: Local filesystem (50GB truly evergreen)
- **No Docker / Redis / WebSocket / iOS**

## Quick Start (Local)

### Backend
```bash
cd unify-backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=OwnerSeeder
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

## Production Deployment (Pars Pack)

1. Upload `unify-backend/` to `/home/username/unify-backend`
2. Upload `frontend/dist/*` to `public_html/`
3. Set cron: `*/5 * * * * cd /home/username/unify-backend && php artisan schedule:run`
4. Create MySQL DB + import migrations
5. Set `.env` values

## Key Features Implemented
- Honor System (self-declared + abuse detection)
- Scheduler with time/exam overlap + Golden Scheduler
- Resource Hub (Evergreen, versioning, LRU, 50GB)
- Polling + Pushe
- Ticketing with escalation
- IT envelope flow ready
- All 14 migrations with fixes (C1–C5, H1–H10, M1–M12)

**Status**: Milestones 1-3 complete. Ready for production deployment.

## Non-Negotiables Followed
- Honor System checkbox only
- Polling + Pushe (no WebSocket)
- 50GB evergreen storage
- No iOS
- Argon2id + Sanctum

Built following exact 05_AGENT_INSTRUCTIONS.md
