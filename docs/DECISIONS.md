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

## Open decisions

_(none — see D-007 for version columns, D-001/011 for imports, D-006 for push)_

