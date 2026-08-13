# Unify V9 — Performance Budgets (v1, TODO-037)

First budgets ever defined for this project. They are staging-measured targets
(see `tests/perf/k6-smoke.js`); budgets labeled **code** are enforced by
inspection/build, budgets labeled **k6/Lighthouse** are enforced in CI/staging.

## Backend / API

| Budget | Value | Enforced by |
|---|---|---|
| DB operations per notification poll (median) | ≤ 2 | code + staging counter |
| Notification poll p95 (staging, seeded 600 users) | < 800 ms | k6 threshold |
| Unified inbox p95 | < 1200 ms | k6 threshold |
| Golden-schedule (cache miss) p95 | < 6000 ms | k6 threshold |
| Golden-schedule (cache hit) p95 | < 300 ms | k6 threshold |
| Any endpoint error rate under load | < 2% | k6 threshold |
| /health worker hold | < 100 ms (probe cached 60 s) | code |
| Bulk import (≤2000 rows) | completes < host max_execution_time | staging run |
| Envelope batch (limit=100) | completes < 120 s | staging run |

## Frontend

| Budget | Value | Enforced by |
|---|---|---|
| Eager JS on cold load | ≤ 550 KB raw / ≤ 180 KB gzip | vite build table review |
| Per-screen lazy chunk | ≤ 8 KB raw each | vite build table review |
| Lighthouse LCP (mid-range Android, Fast 4G) | < 2500 ms | Lighthouse CI (to add) |
| Lighthouse TBT | < 300 ms | Lighthouse CI (to add) |
| API runtime cache | public endpoints only (never private GETs) | code review of sw.ts |

## Data growth

| Budget | Value |
|---|---|
| notifications/messages/audit_logs | purge/archive policy ≤ 12 months retention (implementation pending — tracked) |
| Golden schedule cache | expired rows purged daily (done) |
| laravel.log / auth.log | daily rotation (done) |

## How to measure

1. `k6 run tests/perf/k6-smoke.js` against staging (see file header).
2. Backend query counts: temporary `DB::listen` logger behind a local env flag,
   or MySQL slow-query log on staging.
3. Frontend: `npm run build` chunk table + Lighthouse against the deployed build.
4. Baselines must be re-captured after each P1/P2 performance change merges.

## Measured baselines — 2026-08-12 (TODO-037 first capture)

Environment: Linux sandbox, 2 vCPU Xeon 2.60GHz, 2 GB RAM, PHP 8.4 (CLI server,
6 workers), **MariaDB 11.8** (with one schema shim: `courses_code_lower_unique`
implemented as virtual-column index — the functional-key-parts syntax is
MySQL-8-only; endpoint-relevant paths unaffected), seeded 600 students.
k6 v0.54, profile = repo `tests/perf/k6-smoke.js` (40 VU steady 2m + burst to
120 VU). Login throttle relaxed via `LOGIN_THROTTLE_MAX_ATTEMPTS` env only
(guest-IP-keyed limiter); per-user API throttles (30-60/min) fully intact.
Two consecutive runs, ~11.5k requests each at ~47.7 rps (≈2.4x design point):

| endpoint | p95 run 1 | p95 run 2 | budget | verdict |
|---|---|---|---|---|
| notification poll | 52.3 ms | 45.6 ms | < 800 ms | ✅ 15x headroom |
| unified inbox | 46.8 ms | 42.4 ms | < 1200 ms | ✅ |
| golden schedule | 53.9 ms | 51.5 ms | < 6000 ms | ✅ |
| error rate | 1.61 % | 1.72 % | < 2 % | ✅ (expected burst 429s) |

Slow queries (>500 ms): **zero** across both runs (max single query 52 ms —
DB is not the constraint at this scale). The one iteration-1 warmup outlier on
`golden-schedule` is the compiled-template cold path; re-verify with
`config:cache route:cache` on staging.

**Production truth check still required:** re-run on a staging copy of the real
shared host (Apache, MySQL 8.0, production PHP workers) before the first
enrollment window. Treat these numbers as the regression floor.

Harness correctness notes (fixed with this baseline): load runs must use
`-e STUDENT_POOL=600` — production throttles are per-user, so a single
identity measures the limiter; tokens are memoized per VU because login-per-
iteration measures argon2id CPU (~250-500 ms/login), not the app.
