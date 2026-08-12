/**
 * Unify V9 load smoke (TODO-037 / PERF budgets).
 *
 * Run on a STAGING copy with seeded data only — never against production.
 *   k6 run -e BASE_URL=https://staging.example/api/v1 \
 *          -e STUDENT_ID=400100001 -e STUDENT_PW=... tests/perf/k6-smoke.js
 *
 * Profile: realistic poll-heavy mix at ~20 rps average (the production design
 * point for 600 users), plus an enrollment burst at 3x for 1 minute.
 * Thresholds encode docs/PERF_BUDGETS.md — a throttled 429 on login bursts is
 * EXPECTED (rate limiting works) and is not counted as a failure.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    steady_poll_mix: {
      executor: 'constant-vus',
      vus: 40,
      duration: '2m',
    },
    enrollment_burst: {
      executor: 'ramping-vus',
      startTime: '2m',
      stages: [
        { target: 120, duration: '30s' },
        { target: 120, duration: '1m' },
        { target: 0, duration: '30s' },
      ],
    },
  },
  thresholds: {
    // PERF budgets (see docs/PERF_BUDGETS.md); fail CI/staging runs that regress.
    http_req_failed: ['rate<0.02'],
    'http_req_duration{endpoint:poll}': ['p(95)<800'],
    'http_req_duration{endpoint:inbox}': ['p(95)<1200'],
    'http_req_duration{endpoint:golden}': ['p(95)<6000'],
  },
};

const BASE = __ENV.BASE_URL || 'http://localhost:8000/api/v1';

function token() {
  const res = http.post(`${BASE}/auth/login`, JSON.stringify({
    username: __ENV.STUDENT_ID,
    password: __ENV.STUDENT_PW,
  }), { headers: { 'Content-Type': 'application/json' }, tags: { endpoint: 'login' } });
  const ok = check(res, { 'login 200 or throttled-429': (r) => [200, 429].includes(r.status) });
  return ok && res.status === 200 ? res.json('access_token') : null;
}

export default function () {
  const t = token();
  if (!t) { sleep(5); return; } // throttled: back off (rate limiter = pass)
  const auth = { headers: { Authorization: `Bearer ${t}` } };

  const poll = http.get(`${BASE}/notifications/unread`, { ...auth, tags: { endpoint: 'poll' } });
  check(poll, { 'poll 200': (r) => r.status === 200 });

  const inbox = http.get(`${BASE}/messages?tab=all`, { ...auth, tags: { endpoint: 'inbox' } });
  check(inbox, { 'inbox 200': (r) => r.status === 200 });

  if (__ITER % 5 === 0) {
    const golden = http.get(`${BASE}/golden-schedule`, { ...auth, tags: { endpoint: 'golden' } });
    check(golden, { 'golden 200/404': (r) => [200, 404].includes(r.status) });
  }

  sleep(3);
}
