# Architecture & Operations Decision Records

Dated decisions taken while executing `Reports/UNIFY_TODO_PLAN.md`. Newest last.

## D-001 (2026-08-11) — Heavy-work offloading (TODO-007)

**Decision: synchronous-with-bounding, no queue worker.**

Context: import (≤2000 rows), envelope ZIP (600 PDFs), golden scheduler (≤5 s)
are the only heavy workloads. The Pars Pack Shop shared host offers no reliable
long-running worker process and the project constraints (no VPS/Redis) stand.

Rationale:
- Import: per-row cost is now dominated by inserts, not hashing (temp passwords
  use the reduced argon2id profile, same as envelopes F01), and lookups were
  precomputed (2 queries total). Worst case is ~2 s of hashing + 2000 inserts —
  inside a 60 s request budget. No worker needed.
- Envelopes: capped at 600 users/run with `set_time_limit(600)` and atomic
  password commit; runs twice a year. Keep manual, owner-triggered.
- Scheduler: already hard-bounded (5 s / 1000 combos / 1 h result cache).

Rejected: `queue:work` via cron — no jobs exist anywhere in the codebase, adding
a jobs table + worker loop would be infrastructure for zero current callers.
Revisit only if a real workload outgrows a request budget.

## D-002 (2026-08-11) — Upload storage moves to the local disk (TODO-001)

New uploads (resources and student temp staging) on the `local` disk
(storage/app/...), served only via the authorized download endpoint. The stored
extension is derived from the finfo MIME check, never from the client filename.
Legacy files already under storage/app/public remain downloadable through the
same endpoint (read fallback) until naturally rotated out by the 30-day
superseded-content cleanup. A one-shot `storage cp` migration for legacy files
may be run by ops later; it is not required for correctness.

## D-003 (2026-08-11) — Deploy pipeline consolidation (TODO-003)

deploy.yml is the single production pipeline (SSH key auth, host-key
fingerprint, staging → atomic swap → migrate → artisan caches → health check →
rollback). The old FTP deploy job was removed from ci-cd.yml (now CI-only).
Required GitHub secrets: SSH_HOST, SSH_USER, SSH_PRIVATE_KEY,
SSH_HOST_KEY_FINGERPRINT. FTP credentials are retired.

## D-004 (2026-08-11) — Notification poll cache (TODO-019)

Cache key is per user (5 s TTL, file store). A cache hit may return items from
a marginally older `since` window; the client already de-duplicates by id.
`markRead` forgets the key; inserts rely on the 5 s TTL (worst-case extra
staleness is 5 s on top of a 30 s poll cadence — acceptable, and removes the
per-poll cache-write amplification measured in PERF-01).

## D-005 (2026-08-11) — Publishing permissions (TODO-010)

forms / faqs / academic-calendar POST: expert, admin (professors CANNOT publish
forms per docs/ROLES). notice-board POST: professor, expert, admin (class
notices). Class broadcasts (F07): professor (own specs only), expert, admin,
owner. These are enforced with the existing `role:` middleware at the route
layer; per-resource ownership refinements (e.g. expert limited to own
department) remain open hardening — see TODO-044 (negative test matrix).

## D-006 (2026-08-11) — Push/SMS providers flag-gated, Kavenegar deferred (TODO-031)

PusheService is now wired (approve-resource notify + broadcast fan-out) but only
when `PUSHE_ENABLED=true` (`services.pushe.enabled`). Default is OFF: shared-host
curl fan-out can exceed the 30 s request cap on Pars Pack; polling (30 s) already
covers notification delivery on the national intranet. Kavenegar SMS is NOT
implemented (`kavenegar.enabled` flag + config stub kept for future work) —
SMS costs real money per message and no budget was approved. Decision: push
opt-in, SMS deferred until explicitly requested.

## D-007 (2026-08-11) — Optimistic locking: keep version columns, real protection = transactions

course_specifications.version stays as a display/audit column; we do NOT add
CAS update checks. The audit's lost-update scenarios (enrollment races, grace
auto-finalization) are handled by DB transactions + SELECT ... FOR UPDATE row
locks (EnrollmentController + OfflineSyncController, TODO-016/012) which give
stronger guarantees than app-level version checks on a single-row MySQL setup.

Follow-up (2026-08-13, TODO-042 close): the read-only audit found NO concurrent
content-edit routes at all (no PATCH/PUT on enrollments/resources/tickets/
curriculum_charts), so CAS checks are unnecessary everywhere — D-007's "no CAS"
call stands. But "keep as audit column" only applies where something READS
version: enrollments.version (write-only, unread) and tickets.version (fully
inert) were theater and are dropped in migration 2026_08_12_000002;
resources.version (UI display + F06 chain) and curriculum_charts.version
(unique revision key) stay — they are business data, not lock tokens.

## D-008 (2026-08-11) — Dead tables dropped; live stats in system_configs (TODO-029/030)

storage_stats and course_specification_history had no writers anywhere in the
codebase — dropped in 2026_08_11_000002_integrity_hardening. Storage usage is
tracked in system_configs.storage_used_bytes (written by the upload/delete
pipeline, read by MonitoringController). If spec-history is ever needed it
should be an append-only audit table with a real writer, not a schema ghost.

## D-009 (2026-08-11) — Offline SyncQueue: producers wired, not removed (TODO-028)

The IndexedDB SyncQueue had ZERO producers (dead 2-minute interval polling an
empty queue). Decision: WIRE it (F19 metro promise) rather than remove.
Shipped producer: resource ratings (RateStars → `POST /resources/{id}/rating`;
offline/network-failure → queued intent, replayed by offlineSync.ts — safe
because the backend rating store is an upsert per (student, family)).
'ticket_reply'/'assignment'/'curriculum_checkbox' intents stay whitelisted but
have no UI producers yet; the sticky-note producer ships with its editor UI.
Also: replay now triggers immediately on the `online` event, interval kept as
safety net. Duplicate replay with the SW BackgroundSyncPlugin is benign
(different layers; server upsert idempotent).

## D-010 (2026-08-11) — Supply-chain gates + dependency resolutions (TODO-035)

- react-router-dom 6.22 → **7.18** (fixes the 2 moderate advisories; setupTests
  gained a TextEncoder/TextDecoder polyfill for jsdom; full test+build green).
- CI blocking gates added: `composer audit --no-dev` and
  `npm audit --omit=dev --audit-level=moderate`. Full audits run informational.
  After the router upgrade the production npm tree is **0 vulnerabilities**;
  the 6 remaining advisories are dev-only (vite/esbuild dev server, storybook
  uuid) and excluded from the shipped bundle by `--omit=dev`.
- intervention/image ^2.7: **zero call sites** in app/config/routes — a dead
  dependency, so the 2.x→3.x migration is pointless. Removal
  (`composer remove intervention/image`) queued for the host/CI (needs network;
  sandbox has no composer). TODO-049 may drop php-gd requirement accordingly.

## D-011 (2026-08-11) — Excel import bounded synchronous, capped at 600 (TODO-020 closeout)

Per D-001 (sync-with-bounding), no queue/chunk-token machinery. Final guards:
MAX_ROWS 2000 → 600 (one intake = ~600 students; larger files must be split),
two-pass validate→insert (a doomed file now costs zero password hashes),
`@set_time_limit(120)` outer bound, upload progress UI on both import screens.
Client-side chunking was rejected: it breaks the all-or-nothing transaction
semantics that the error-report flow (red-column XLSX back) depends on.

## D-012 (2026-08-11) — One deletion strategy per table; SoftDeletes retired (TODO-029)

Audit found triple semantics: `deleted_at` columns + an unused-on-most-tables
SoftDeletes trait + app-level flags, with **zero** `Model::delete()` call
sites. Blanket-adding the trait (attempted earlier in this cleanup) was a
behavioral REGRESSION for messages: their `deleted_at` tombstone would be
hidden by the global scope while F07 mandates a visible "این پیام توسط
فرستنده حذف شد" placeholder.

**The single strategy per table (all app-level, no Eloquent global scopes):**

| Table | Strategy |
|---|---|
| messages | tombstone — `is_deleted=true` + `deleted_at` timestamp + placeholder body; row stays visible |
| resources | content tombstone — `is_deleted_content=true` (file bytes purged by LRU/cleanup, row stays listed); `is_superseded` for old versions |
| forms | `is_active` flag |
| users | `is_banned` (+ `banned_*` audit fields); accounts are never deleted |
| courses, tickets, course_specifications, faqs, notice_boards, academic_calendars, enrollments | no delete flow exists; if one is added later it is a HARD delete (+ FK behavior), or a new documented flag — never the trait |

The SoftDeletes trait is removed from ALL models (Course/Ticket included) and
the nullable `deleted_at` columns remain in schema as reserved, unused. Any
future use requires updating this decision first.

Follow-up (2026-08-12): migration 000014's `softDeletes()` adds are now
`hasColumn`-guarded — the unguarded block crashed fresh MySQL installs with
1060 "Duplicate column 'deleted_at'" because 000008 creates the live
`messages.deleted_at` tombstone column directly. New migration
`2026_08_12_000001_ensure_messages_deleted_at` additionally guarantees that
one LIVE column on production DBs whose pre-revision 000014 swallowed the
same duplicate error and will never re-run.

## D-013 (2026-08-11) — Enrollment uniqueness is (course × professor) per term (TODO-041, closed)

Product rule (owner decision): a student may enroll in **one section per
course-per-professor per semester** — "Calculus 1 with Prof. Ahmadi only once
per term". Retakes of the same combo in a LATER term are explicitly allowed
(fail/improve), so the rule is NOT course-wide and NOT cross-term.

Implementation: a guard inside `storeTemp`'s existing locked transaction —
the duplicate check reuses the student's current-semester enrollment snapshot
(already `lockForUpdate`), matching on `specification.course_id +
professor_id`; violation → 409 `COURSE_PROF_DUPLICATE`
("این درس را با این استاد در این نیم‌سال قبلاً اخذ کرده‌اید").
No denormalized DB unique index: cross-table uniqueness (enrollment → spec →
course/professor) cannot be expressed as a native constraint without denorm
columns, and the per-student row lock makes the app-level check race-safe
(D-007 philosophy). Pinned by EnrollmentCourseProfUniquenessTest (3 tests).

## D-014 (2026-08-12) — Supply-chain gate fired: 12 of 15 advisories fixed, 3 framework exceptions

First `composer audit --no-dev` gate run (TODO-035) reported 15 advisories on
3 packages. Resolution:

- **dompdf/dompdf 2.0.8 → 3.1.6** (6 advisories: SVG file-leak, BMP/DoS…).
  Fix only exists on the 3.x line, so **barryvdh/laravel-dompdf ^2 → ^3.1.2**
  alongside; dompdf 3.x replaces phenx/php-{font,svg}-lib with
  dompdf/php-{font,svg}-lib. composer.lock spliced with full Packagist
  records; content-hash recomputed with Composer's exact algorithm.
- **league/commonmark 2.8.3 → 2.10.0** (6 DoS advisories; in-range for
  laravel/framework's ^2.7, no constraint change needed).
- **laravel/framework 11.55 — 3 advisories REMAIN, accepted & documented:**
  PKSA-3r5d / PKSA-mdq4 (CRLF injection in the default `email` validation
  rule, high) and PKSA-m5cs (temporary signed-URL path confusion, medium).
  **No 11.x patch exists upstream** (EOL'ing line) — BUT both are
  **not exploitable in this codebase**: zero `'email'` validation rules and
  zero signed routes (grep-verified 2026-08-12). The CI gate ignores exactly
  these three IDs (see ci-cd.yml); anything new fails the build.

**Follow-up task (L12 upgrade):** retire the exception list by upgrading to
Laravel ≥12.61.1 on a composer-capable machine:
`composer require "laravel/framework:^12.61.2" -W` → run test suite → CI
green → remove the 3 IDs from the gate ignore list. L12 supports PHP ^8.2
and is an officially "maintenance-focused" upgrade from 11.

## D-015 (2026-08-13) — TODO-046 closed: client cache layer REJECTED with evidence; micro-fixes landed

Gate: TODO-037 baselines (2 runs, ~11.5k reqs @ ~48 rps, 600 users): p95 ≤ 54 ms on
poll/inbox/golden, zero slow queries → the backend feels no duplicate-fetch pain, so
react-query/SWR/axios-cache-interceptor is **rejected** (bundle cost + a second cache
to invalidate beside the offline SyncQueue, for zero measured benefit).

Client-side read-only audit findings and what was done with them:
- /health was double-polled every 15 s (Layout + ServerBanner each started their own
  interval) → serverStatus.ts is now ONE shared poller with subscriber fan-out.
- Professor/StudentsList fired GET /enrollments + /specifications and DISCARDED both
  responses → placeholder screen is now static, zero network.
- Cross-screen refetches (7 screens re-fetch /specifications on navigation) were
  evaluated and deliberately left as-is: a TTL map would trade 50 ms of network for
  staleness bugs during the enrollment window — wrong trade for this app.
- The TODO's preconnect micro-fix was examined and SKIPPED as a no-op: the API is
  same-origin (/api/v1 on the same vhost), so the connection is already warm.
- Reopen trigger (recorded honestly): if client profiling (Network tab / RUM) ever
  shows ≥2 identical GETs per screen mount or render waterfalls > 500 ms on mid-range
  Android, re-run this decision.

## D-016 (2026-08-13) — Post-plan adversarial audit: 6 MEDIUM + 10 LOW findings closed in one batch

An independent read-only deep audit of the post-49/49 tree (report:
`Reports/UNIFY_DEEP_TECHNICAL_AUDIT_POST_REMEDIATION.md`) found no CRITICAL/HIGH
but six MEDIUM "policy in the wrong layer" defects. All fixed and pinned by
14 new tests (67 → 81 backend, suite re-verified green on sqlite AND MariaDB):

- F-01 `must_change_password` is now enforced by `EnsurePasswordChanged`
  middleware (was a frontend-only redirect; temp passwords gave full 7-day API
  access). Allowlist: onboarding / password/change / logout / users/me.
- F-02 `finalize()` revalidates credit cap + time/exam overlaps against the
  CURRENT declared status inside one locked transaction (closes the
  declare-high → add → declare-low → finalize bypass).
- F-03 Form publishing is scope-bound: expert ⇒ own department only, never
  university-level; admin ⇒ either (F10 fan-out to students implemented for
  real per product decision — type 'general', ENUM untouched).
- F-04 `AuditLog::record()` choke point now feeds the audit trail from 12
  privileged sites; sqlite's audit_logs.action enum-CHECK was widened to match
  MySQL (migration 2026_08_13_000001).
- F-05 CI gained a MySQL dialect-parity test leg (the functional-index
  incompatibility that broke MariaDB is fixed at the migration: MariaDB now
  skips it, relying on utf8mb4 collation semantics).
- F-06 Default per-user throttle (120/min) on the whole sanctum group —
  sync/devices/notifications/forms were completely unthrottled.
- LOW batch: F-07 per-intent Idempotency-Key (client keeps the key across
  retries; server validates ≤36 chars and replays on unique races) · F-09 SW
  BackgroundSync removed (single queue = app SyncQueue) · F-11 no raw exception
  text in import 500s · F-12 Pushe timeouts 5s/3s · F-13 message tombstone
  scrubs subject too; atomic broadcast throttle (row-locked conditional
  UPDATE); retry_after always seconds · F-14 axios 30s default timeout;
  last_poll validated · F-15 DELETE /devices + logout hook · F-16 cross-midnight
  overlap day-pairs; atomic daily quotas (upload/download, row-locked); orphan
  upload cleanup; no 'CS' magic fallback in approver notify; misleading bcrypt
  'rounds' option removed; IntranetDetector dedup · F-10 seeded demo
  database.sqlite KEPT (WINDOWS_RUN zero-tooling feature) but runtime
  token/audit tables purged; 6 empty seeder stubs deleted.

Deliberately NOT changed: health endpoint version disclosure (purpose-built),
students' DM reachability (product question parked), system-message backend
branch (harmless; UI tab removed).

## D-017 — Round-2 audit remediation wave (2026-08-13, audit file `UNIFY_DEEP_TECHNICAL_AUDIT_ROUND2_POST_FIXES`)

All 17 Round-2 findings (7 MEDIUM / 10 LOW) closed. Product forks decided by
the owner: **ticketing gets real expert lanes** (built end-to-end), **the dead
Kavenegar SMS service is deleted** (not kept dormant).

- V2-01 axios per-call timeout overrides on every long-leg operation
  (uploads 300s, downloads 120–180s, imports 120–180s, envelope ZIP 620s);
  the 30s interactive default stays.
- V2-02 `users.ticket_lane` (migration 2026_08_13_000002): expert/head inboxes,
  canView/status/assign all lane-scoped; admin/owner all-lane; lane-less staff
  get an explicit empty inbox. Provisioning via ExpertSeeder + the optional 7th
  import column (زمینه تیکت). New `/expert/tickets` screen.
- V2-03 ticket payloads trimmed to User::PUBLIC_COLS (student/assignedExpert/
  replies.sender) — SEC-04 made uniform. Pinned by TicketPrivacyTest.
- V2-04 curriculum create/submit role-gated to expert,admin (403 for students).
- V2-05 versions share the atomic 5/day upload quota; new cron
  `files:cleanup-staging` sweeps 24h+ orphans and auto-rejects 14d pendings.
- V2-06 LRU cleanup resolves the actual disk per row (local first, legacy
  public fallback) and only tombstones on a real delete; limit is
  config-overridable (`unify.lru_limit_bytes`) for tests.
- V2-07 owner envelopes page gained the bulk ZIP section (scope students/
  staff/all) calling the previously caller-less `/owner/generate-envelopes`.
- V2-08 audit viewer decodes plain JSON first, legacy Crypt rows second;
  login audit details switched to plain JSON (no-secrets policy uniform).
- V2-09 spec import validates/normalizes times per row (`H:MM` → `HH:MM`),
  rejects inverted ranges and unknown semesters; also now binds
  specs to courses by ROW ID (FK target) instead of by code — factory-created
  courses (id≠code) crashed imports before.
- V2-10 semester create: 422 BAD_DATE/DUPLICATE_SEMESTER/BAD_RANGE instead of
  silent now()-fallbacks and PK-500s; audit row records the real semester id.
- V2-11 ticket quota now uses the shared `App\Services\DailyQuota` primitive
  (extracted from ResourceController; adopted by upload/download/version).
- V2-12 branding resolves the Laravel `/storage/...` URL space (docs/01's
  uploads-symlink line corrected; DEPLOYMENT §5 checks `storage:link`).
- V2-13 broadcast retry_after = exact remainder seconds.
- V2-14 `App\Services\ResourceRatingRecalc` shared by the online endpoint and
  /offline/sync — synced ratings recompute the family head score.
- V2-15 XLSX export cells formula-guarded (`=+-@` prefix-escaped).
- V2-16 sticky-note upsert keeps its PK (firstOrNew pattern).
- V2-17 hygiene: 51 tracked session fixtures `git rm`'d (+ gitignore);
  KavenegarService/config/env/docs purged; `setBrandName` routed
  (`POST /admin/branding/name`) and the admin screen's name field now persists;
  professor grading scoped to own-spec trackers; idempotent replays honor
  `expires_at` (expired keys are freed and reprocessed, not echoed);
  notification mutes are honored in the unread feed and invalidate its cache.

**Latent crashers surfaced by the new tests (all fixed, test-pinned):**
`ShamsiService::isValid` called a non-existent `Jalalian::isValid()` (fatal on
every spec import carrying an exam date + every semester validation);
`notification_mutes` composite PK made Eloquent updates silently no-op (unmute
could NEVER work); curriculum submit fell back to a hardcoded owner id
'990000001' (FK-crash when absent) — now notifies head(s) or admins.

## Open decisions

_(none — version columns settled by D-007 + TODO-042 follow-up; see D-001/011 imports, D-006 push)_

