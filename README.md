# Unify V9 — University Assistant System

Persian RTL university assistant for **600 Computer Engineering students**, built for
**Pars Pack Cloud Host** (shared hosting, Iran) — **no VPS, no Redis, no WebSocket, no iOS**.

- **Backend:** Laravel 11 + PHP 8.2 (Argon2id, Sanctum token expiry, cron scheduler)
- **Frontend:** React 18 + TypeScript + Vite + PWA (Workbox) + MUI RTL + Android (Capacitor, optional)
- **DB:** MySQL 8 in production · SQLite for the local/Windows demo
- **Real-time:** polling 30s/120s + 5s per-user file cache + Pushe push (PHP curl)
- **Storage:** 50GB evergreen resource hub + daily LRU fallback cron

The full product specification (requirements, features F01–F20, pages P00–P18, API
contract, acceptance criteria) lives in [`docs/`](docs/).

## Project structure

```
unify-backend/    Laravel API — the single source of truth for the backend
                  (serves the pre-built SPA from public/ in single-server mode)
frontend/         React PWA source (Vite) + Capacitor Android wrapper
docs/             Product specification (Final_Project docs 00–23)
.github/          CI/CD workflows (CI: MySQL 8 tests + build · deploy.yml: single SSH pipeline)
tests/perf/       k6 smoke load script (see docs/PERF_BUDGETS.md)
```

## Quick start — local demo (Windows / SQLite, single server)

Requires only PHP 8.2+ and Composer (a pre-seeded SQLite DB with demo accounts is included):

```bash
cd unify-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
# open http://localhost:8000
```

Demo accounts (IT-handout flow): student `400100001`, owner `990000001`, professor `P1001` …
see [WINDOWS_RUN.md](WINDOWS_RUN.md) for full credentials and details.

## Quick start — development (frontend rebuild)

```bash
cd frontend
npm install
npm run dev        # vite dev server
npm run build      # output: dist/ — copy into unify-backend/public/ for single-server mode
npm test           # jest suites (npx jest)
```

## Production deployment (Pars Pack, MySQL 8)

See [DEPLOYMENT.md](DEPLOYMENT.md) and `docs/02_DEPLOYMENT_GUIDE.md`.
Summary: upload `unify-backend/` outside `public_html`, build frontend into
`public_html`, switch `.env` to `DB_CONNECTION=mysql`, run migrations, set the
`*/5 * * * * php artisan schedule:run` cron. CI deploys via `deploy.yml` (SSH key
auth + host-key fingerprint — secrets `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`,
`SSH_HOST_KEY_FINGERPRINT`). Push notifications are opt-in: set `PUSHE_ENABLED=true`
plus `PUSHE_API_KEY` (default off — polling already covers delivery).

Notable API additions since V9 audits: `POST /auth/logout` (token revocation),
`POST /admin/semesters/activate` (enrolling→active + grace start),
`POST /admin/import/{courses,specifications}`, `GET /owner/stats` (aggregate
analytics). Uploads store on the local disk with a MIME→extension allowlist;
Sanctum personal-access tokens expire after `SANCTUM_TOKEN_EXPIRATION_MINUTES`
(default 10080 = 7 days).

Also available: [WINDOWS_RUN.md](WINDOWS_RUN.md) (local demo) ·
[DISASTER_RECOVERY.md](DISASTER_RECOVERY.md) · [start-codespace.sh](start-codespace.sh) ·
[.devcontainer/](.devcontainer) (GitHub Codespaces).

**Ops & audit trail:**
[docs/DECISIONS.md](docs/DECISIONS.md) (architecture decisions D-001…D-012,
incl. push-notifications policy and deletion strategies) ·
[docs/CRON_TABLE.md](docs/CRON_TABLE.md) (verified scheduler table) ·
[docs/HOST_VERIFICATION.md](docs/HOST_VERIFICATION.md) (production
verification checklist + uptime probe) ·
[docs/PERF_BUDGETS.md](docs/PERF_BUDGETS.md) + [tests/perf/k6-smoke.js](tests/perf/k6-smoke.js)
(k6 harness; baselines pending a prod-like run).

## Top features

Honor System (self-declared academic status) · Scheduler with time/exam-overlap checks +
Golden Scheduler (MRV backtracking) · Evergreen Resource Hub (versioning, ratings, LRU
50GB) · Unified messaging · Ticketing with escalation cron · Curriculum charts ·
Transactional Excel import/export · IT-handout envelope ZIP (PDF + QR) · Offline sync
(PWA + IndexedDB) · Polling + Pushe notifications that work on the national intranet.
