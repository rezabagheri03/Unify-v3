# F03 Scheduler Phases A/B/C + Grace Period - V9 Shared Host (Laravel + Cron + Polling)

## Phases
### Phase A Enrollment (global_state=enrolling)
UI Search bar Name/Code debounce, filters dept credits day, list available specs where semester=current is_active=1, spec card Course Name bold Code Professor Day Time Location Credits Exam date Add button "افزودن" primary if not in temp else "حذف", Add checks time overlap day_of_week, exam overlap, prereq warning modal, credit limit warning, if passes adds to temp list
Temporary List sidebar/bottom sheet total credits conflict warnings delete, Clear all confirmation
Final Submit button bottom sticky disabled if credit violation honor not declared empty, confirmation modal "آیا از نهایی‌سازی {credits} واحد مطمئنید؟", POST /api/v1/enrollment/final idempotency key MySQL table IdempotencyKeys, on success snackbar navigates to active view
API: GET /api/v1/specifications?semester=current&search=&day=&page=, POST /api/v1/enrollment/temp {spec_id}, GET /api/v1/enrollment/temp, DELETE /api/v1/enrollment/temp/{id}, POST /api/v1/enrollment/final, GET /api/v1/users/me/academic-status, GET /api/v1/golden-schedule, polling for spec_updated event? Polling every 15s checks if spec in temp list time changed -> critical banner
Offline: Add/remove temp queued IndexedDB, final requires online, golden offline via Web Worker cached data

### Phase B Active (global_state=active)
Graphical weekly timetable Sat-Wed 8-18 time blocks, specs placed day/time blocks colored hash professor_id, height proportional duration, click Details modal, read-only, button "مشاهده برنامه امتحانات" navigates to /scheduler/exam
GET /api/v1/enrollments?semester=current&status=finalized, cached offline, overlap handling side by side 50% red border if final_semester conflict

### Phase C Exam Mode
Button "مشاهده برنامه امتحانات" appears when global_state exam OR calendar exam_period_start within 14 days, FlipCard Framer Motion Front weekly Back linear exam list sorted final Gregorian asc, Front weekly Back linear final blue badge midterm orange, reduced motion fallback fade opacity, Back list rows Course Prof Code Final Date Shamsi Day Time Location Midterm orange, countdown "5 روز مانده", sortable, same enrollments specs exam dates

### Grace Period 24h - Cron replaces Celery
Trigger Admin switches enrolling->active sets grace_period_ends_at now+24h Asia/Tehran UTC, AuditLog, Cron every minute checks if now>=grace_ends and handled=0 -> hard delete temporary enrollments, handled=1, notify affected via polling + Pushe "لیست موقت حذف شد", Client countdown banner red <2h, Add new temp disabled during grace only Final Submit enabled, Final after grace 403, defining new semester while grace active blocked

## Global State Management Admin
Current semester name global_state dropdown enrolling/active/exam grace ends countdown, Buttons Define New Semester (creates semester is_current true old false soft hide old specs is_active 0 enrollments finalized->archived temp hard deleted notification to all), Switch Phase radio confirmation typing semester name warning enrolling->active starts 24h

## API
PATCH /api/v1/admin/semesters/{id}/global-state {global_state}

## Notifications
Phase change polling, grace start/end, exam activation via polling + Pushe PHP curl
