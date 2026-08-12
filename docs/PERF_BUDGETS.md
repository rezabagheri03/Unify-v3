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
