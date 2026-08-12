# 18 - Performance Budget - V9 Shared Host - 600 Students

## Goals

- Initial load <2s on 3G (Iran 3G average 1-2 Mbps)
- Lighthouse score >90 performance, >90 accessibility, >90 best practices
- Bundle size <300KB gzipped JS, <50KB CSS, <500KB total initial
- API response p95 <300ms for GET specs, <500ms for POST enrollment final, <200ms for polling notifications
- Polling 600 users * 1 req/30s = 20 req/s average, with 5s file cache hit 80% -> 4 req/s DB queries

## Frontend Budget

### Bundle Size

- React 18 + Vite: ~140KB gzipped (react-dom 40KB, scheduler, etc.)
- Zustand + idb-keyval: ~5KB
- MUI v5: Heavy ~300KB gzipped if full import, must code-split and tree-shake: Use `import { Button } from '@mui/material/Button'` not `import { Button } from '@mui/material'`, or replace MUI with Tailwind + shadcn for lighter bundle (Tailwind 10KB + shadcn components 20KB = 30KB vs MUI 300KB). For MVP, keep MUI but code-split: `React.lazy()` for heavy pages Resource Hub, Curriculum Charts, etc.
- Framer Motion: ~30KB gzipped, only for exam flip, lazy load
- date-fns-jalali: ~10KB
- Total JS initial: Target <300KB gzipped, currently with MUI full ~400KB, need to optimize to <300KB via code-splitting and Tailwind alternative

### Code Splitting

- Route-based splitting: `React.lazy(() => import('./screens/Student/Dashboard'))`, `SchedulerA`, `ResourceHub`, etc.
- Component-based splitting: `FileCard`, `CourseCard` heavy? Keep in main bundle, but `Timeline` (Academic Calendar) lazy, `Kanban` (Assignment Tracker) lazy

### Images

- Logo max 2MB PNG/SVG, thumbnails 128,64,32 via Intervention Image
- File preview PDF first page via pdf.js lazy loaded, not initial bundle
- Icons: MUI icons ~100KB, use Lucide icons tree-shakable 20KB

### CSS

- MUI CSS ~50KB gzipped, Tailwind ~10KB purged
- Custom CSS variables for themes, not heavy

### PWA Caching

- Workbox runtime caching: GET /api/v1/specifications* CacheFirst 5min, GET /api/v1/enrollments* CacheFirst 5min, GET /api/v1/resources* metadata CacheFirst 5min, static assets JS/CSS Fonts CacheFirst, offline.html NetworkFirst
- Cache API LRU 100MB for file content PDFs/DOCXs, eviction via Workbox LRU 100MB, protected professor files never evicted (is_protected)

### Core Web Vitals

- LCP <2.5s, FID <100ms, CLS <0.1
- LCP is Course Cards List or Weekly Timetable, optimize via virtualized react-window, skeleton loading, not spinner
- CLS: Avoid layout shift when polling notification banner appears - reserve space via min-height

## Backend Budget

### API Response Time

- GET /api/v1/specifications?semester=current&search=&day=&page= : p95 <300ms, needs index (semester_id, is_active, day_of_week) composite, already in migration, plus MySQL query cache? File cache for polling endpoint 5s per user reduces DB hits
- POST /api/v1/enrollment/final: p95 <500ms, needs transaction + version check + credit limit + time overlap check day_of_week, should be <500ms for 600 students
- GET /api/notifications/unread?since=: p95 <200ms, needs index (user_id, read, created_at) composite, plus file cache 5s per user `Cache::remember("notifications:{$user_id}", 5, ...)` to reduce DB hits 80%

### Database

- MySQL max_connections 100-200 on shared host, need to keep concurrent connections low: Each Laravel request opens 1 connection for ~50-100ms, 20 req/s *0.1s = 2 concurrent average, okay, but peak enrollment 200 concurrent finalizing at same second = 200 concurrent connections -> exceeds max_connections, need file cache for polling + increase polling interval 30s (already fixed C5) + stagger enrollment times via lottery: final year first hour, GPA_A second hour, normal third hour, conditional fourth hour
- Query optimization: Use `EXPLAIN` for common queries archive dropdown `SELECT DISTINCT semester_id FROM enrollments WHERE student_id=self AND status=archived` needs index (student_id, status, semester_id) composite FIX M3, inbox query WHERE recipient_id=self OR (specification_id IN my enrolled spec ids) needs index (recipient_id, sent_at) and (specification_id, sent_at)
- N+1: Use Eloquent eager loading `with(['course', 'professor'])` for specs list, not lazy

### File Storage 50GB

- Shop 10GB base + 40GB extra block storage = 50GB total, truly evergreen for 2-3 years
- LRU cleanup cron daily checks /uploads/resources size >40GB (80% of 50GB) until <35GB (70%), deletes least recently downloaded non-protected (is_protected false) + is_superseded false (don't delete current versions, only old? Actually for LRU we delete oldest accessed)
- Download daily limit 20 per student per day via download_daily_counts table to stay under fair usage 2TB/month

### Cron

- Every 5 min `*/5 * * * * php artisan schedule:run` instead of every minute (shared host limit), plus lazy check fallback in EnrollmentController@final
- Jobs: enrollments:wipe-grace everyFiveMinutes, tickets:escalate hourly, calendar:warn daily 08:00, resources:cleanup-old-versions daily 03:00, files:lru-cleanup daily 04:00, idempotency:cleanup daily 02:00, storage:calculate-stats daily 01:00

### Bandwidth

- Unlimited marketing fair usage ~2TB/month, 600 students *10 resources*5MB=30GB/day = 900GB/month okay, but 50 resources*50MB=1.5TB/day=45TB/month suspend, implement download daily limit 20 per student per day

## Lighthouse Targets

- Performance >90, Accessibility >90, Best Practices >90, SEO >90, PWA >90 (installable, offline fallback, manifest)

## Testing Performance

- k6 script `tests/performance/enrollment_peak.js`: 200 concurrent students finalizing 18 units same second, check p95 <500ms for POST /api/v1/enrollment/final, no 503
- k6 script `tests/performance/polling.js`: 600 users polling every 30s GET /api/notifications/unread, 20 req/s average, check p95 <200ms, no slow query, MySQL slow query log <1% slow queries

## Optimization Checklist for LLM

- [ ] Code-split routes via React.lazy
- [ ] Tree-shake MUI imports via `import { Button } from '@mui/material/Button'` not `from '@mui/material'`
- [ ] Lazy load pdf.js, Framer Motion, Timeline, Kanban
- [ ] Virtualized lists react-window for Course Cards, FileCards, Messages, Tickets
- [ ] Skeleton loading not spinner for Course Cards, FileCards
- [ ] Reserve space for polling banner min-height to avoid CLS
- [ ] File cache 5s per user for polling endpoint
- [ ] Composite indexes for common queries (student_id,status,semester_id), (recipient_id,sent_at), (specification_id,sent_at), (user_id,read,created_at)
- [ ] Eager loading with() for specs list
- [ ] Cron every 5 min not every minute + lazy check fallback
- [ ] Download daily limit 20 per student per day
- [ ] LRU cleanup cron daily + is_protected never evicted
- [ ] Offline.html fallback page for PWA
- [ ] Bundle size check `npm run build` output <300KB gzipped JS initial

END PERFORMANCE BUDGET
