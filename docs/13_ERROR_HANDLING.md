# 13 - Error Handling - Standardized Persian Messages - V9 Shared Host

This doc defines standard error responses, codes, Persian messages that an agentic LLM must use for ALL API endpoints, matching OpenAPI spec and FEATURES docs.

## Global Error Response Shape

All errors return JSON:

```json
{
  "message": "پیام فارسی کلی",
  "errors": {
    "field_name": ["پیام خطای فیلد 1", "پیام خطای فیلد 2"]
  },
  "code": "ERROR_CODE",
  "retry_after": 15 // only for 429
}
```

- `message`: Persian user-friendly message, shown in snackbar red
- `errors`: object field -> array of messages, shown under form field inline red
- `code`: English constant for frontend logic, e.g., TIME_OVERLAP, CREDIT_LIMIT, QUOTA_EXCEEDED
- `retry_after`: seconds, only for 429 rate limit

## HTTP Status Codes Mapping

### 200 OK - Success
- GET list, POST create success, PATCH update success
- Example: Enrollment temp added, Resource rated

### 201 Created - Resource created
- POST /api/v1/resources/upload, POST /api/v1/tickets, POST /api/v1/assignments

### 400 Bad Request - Business logic violation (not validation)
- Credit limit exceeded even after honor: `{"message": "سقف واحد برای حالت عادی 20 واحد است - شما 21 واحد انتخاب کرده‌اید", "code": "CREDIT_LIMIT_EXCEEDED"}`
- Time overlap for non-final_semester: `{"message": "تداخل زمانی با ریاضی 2 شنبه 8-10", "code": "TIME_OVERLAP", "errors": {"conflicting_specs": ["spec123"]}}`
- Exam overlap: `{"message": "تداخل امتحان نهایی با فیزیک 1 - هر دو 1403/04/20", "code": "EXAM_OVERLAP"}`
- Prereq warning? Actually prereq is warning not block, so no 400, returns 200 with warning field: `{"message": "پیش‌نیاز ریاضی 1 را پاس نکرده‌اید، ادامه می‌دهید؟", "code": "PREREQ_WARNING", "prerequisite": "CS101", "can_continue": true}`
- Grace period ended: `{"message": "مهلت نهایی‌سازی تمام شده - لیست موقت شما حذف شد", "code": "GRACE_PERIOD_ENDED"}`
- Defining new semester while grace active: `{"message": "مهلت 24 ساعته فعال است - نمی‌توان ترم جدید تعریف کرد", "code": "GRACE_ACTIVE"}`
- Student tries add new temp during grace: `{"message": "فاز ثبت‌نام بسته شده - فقط نهایی‌سازی لیست موجود ممکن است", "code": "ENROLLING_CLOSED_GRACE"}`
- Finalize after grace: `{"message": "مهلت نهایی‌سازی تمام شده", "code": "GRACE_ENDED"}`
- Reply to closed ticket: `{"message": "تیکت بسته شده - تیکت جدید ثبت کنید", "code": "TICKET_CLOSED", "suggested_action": "create_related", "related_id": "ticket123"}`

### 401 Unauthorized - Not logged in or token expired, generic to avoid enumeration for login

- For auth endpoints, always generic: `{"message": "نام کاربری یا رمز اشتباه است", "code": "INVALID_CREDENTIALS"}` for login fail (both user not found and password wrong same message to prevent enumeration)
- For other endpoints, token expired: `{"message": "نشست شما منقضی شد - دوباره وارد شوید", "code": "UNAUTHENTICATED"}` -> frontend redirects /login + clear token + snackbar

### 403 Forbidden - Banned, expired temp, role insufficient, cannot message banned, cannot reply closed, etc.

- Banned user: `{"message": "حساب شما بن شده: تقلب در انتخاب واحد", "code": "BANNED", "banned_reason": "تقلب در انتخاب واحد", "banned_at": "2024-07-19T10:00:00Z"}`
- Temp expired: `{"message": "رمز موقت منقضی شده - به IT مراجعه کنید", "code": "TEMP_EXPIRED", "expired": true}`
- Role insufficient: `{"message": "دسترسی ندارید", "code": "FORBIDDEN", "required_role": "admin"}` -> frontend shows /403 page
- Cannot message banned (Expert tries message banned): `{"message": "کاربر بن شده - فقط ادمین می‌تواند پیام دهد", "code": "CANNOT_MESSAGE_BANNED"}`
- Cannot message banned for targeted messaging generic to prevent enumeration: Actually for targeted messaging we return generic "کاربر یافت نشد یا دسترسی ندارید" for not found or not in dept or banned, to prevent enumeration, with code `USER_NOT_FOUND_OR_NO_ACCESS`
- Cannot reply to closed ticket: Already covered 403 with code TICKET_CLOSED
- Enrollment final requires online: If offline and tries final (should be disabled in UI but if API called) -> `{"message": "برای نهایی‌سازی نیاز به اینترنت است", "code": "REQUIRES_ONLINE"}`

### 404 Not Found

- Spec deleted: `{"message": "این مشخصه حذف شده", "code": "SPEC_NOT_FOUND"}`
- Resource hard deleted: `{"message": "این جزوه حذف شده", "code": "RESOURCE_DELETED"}`
- Old version file content hard deleted after 30d: `{"message": "فایل این نسخه حذف شده", "code": "FILE_CONTENT_DELETED", "status": 410}` Actually 410 Gone more appropriate, but use 404 with code
- Form file deleted: `{"message": "این فرم حذف شده", "code": "FORM_DELETED"}`
- Ticket not found: `{"message": "تیکت یافت نشد", "code": "TICKET_NOT_FOUND"}`
- User not found for targeted messaging generic: `{"message": "کاربر یافت نشد یا دسترسی ندارید", "code": "USER_NOT_FOUND_OR_NO_ACCESS"}` - same message for not found, not in dept, banned (to prevent enumeration)

### 409 Conflict - Version conflict, idempotency duplicate returns previous response, time overlap via optimistic locking

- Time overlap via version field: `{"message": "تداخل زمانی با ریاضی 2 شنبه 8-10 - نسخه سرور جدیدتر است", "code": "CONFLICT_TIME_OVERLAP", "server_state": {"enrollments": [...], "conflicting_specs": [...]}}` -> frontend shows conflict resolver modal Keep Server / Keep Mine / Merge
- Idempotency duplicate: If client sends same Idempotency-Key twice, server returns previous response with same code and body, not 409, but 200 with previous body (idempotent). Logically not error, but return 200 with `X-Idempotent-Replayed: true` header
- For enrollment final with conflict now because spec changed after temp added: `{"message": "برنامه شما به دلیل تغییر زمان درس ریاضی 2 دچار تداخل شد - لطفا تداخل را حل کنید", "code": "ENROLLMENT_CONFLICT_NOW", "conflicting_specs": ["spec123", "spec456"], "server_enrollments": [...]}`

### 422 Validation Error - Field validation failed

```json
{
  "message": "اعتبارسنجی ناموفق",
  "code": "VALIDATION_ERROR",
  "errors": {
    "shamsi_date": ["تاریخ شمسی نامعتبر - مثال صحیح: 1403/04/20"],
    "time_end": ["زمان پایان قبل از شروع"],
    "file": ["فرمت فقط PDF و DOCX", "حجم فایل حداکثر 50 مگابایت"],
    "day_of_week": ["روز هفته الزامی است - شنبه/یکشنبه/..."],
    "telegram_link": ["لینک تلگرام باید با https://t.me/ شروع شود"],
    "academic_status": ["وضعیت آموزشی الزامی است"],
    "acknowledged": ["تایید مسئولیت خوداظهاری الزامی است"]
  }
}
```

- Shamsi invalid 1403/13/40: `{"field": "shamsi_date", "message": "تاریخ شمسی نامعتبر - مثال صحیح: 1403/04/20"}`
- Time End before Start: `{"field": "time_end", "message": "زمان پایان قبل از شروع"}`
- File size 51MB: `{"field": "file", "message": "حجم فایل حداکثر 50 مگابایت"}`
- File mime exe renamed pdf: `{"field": "file", "message": "فرمت فقط PDF و DOCX"}`
- Duplicate ID within Excel file: `{"field": "id", "message": "شناسه تکراری در فایل - ردیف 5 و 8"}`
- Duplicate ID in DB for Users: `{"field": "id", "message": "شناسه {id} قبلا وجود دارد - ردیف {row}"}`
- File size 5MB image for ticket 6MB: `{"field": "attachments", "message": "حجم هر تصویر حداکثر 5 مگابایت"}`
- 4th image: `{"field": "attachments", "message": "حداکثر 3 تصویر"}`
- Title required: `{"field": "title", "message": "عنوان الزامی است - حداکثر 255 کاراکتر"}`
- Supplementary max 500: `{"field": "supplementary_details", "message": "اطلاعات تکمیلی حداکثر 500 کاراکتر"}`

### 429 Rate Limit - Too Many Requests

```json
{
  "message": "تعداد درخواست زیاد - 15 دقیقه صبر کنید",
  "code": "RATE_LIMIT_EXCEEDED",
  "retry_after": 900
}
```

- Login 6th attempt per 15min per IP: 429 with Retry-After 900 seconds (15min)
- Broadcast 2nd within 10min per professor per spec: `{"message": "لطفا 10 دقیقه صبر کنید - هر 10 دقیقه یک پیام همگانی", "code": "BROADCAST_RATE_LIMIT", "retry_after": 600}`
- Targeted messaging 11th per minute per expert: `{"message": "حداکثر 10 پیام در دقیقه", "code": "TARGETED_RATE_LIMIT", "retry_after": 60}`
- Ticket create 6th per day per student: `{"message": "حداکثر 5 تیکت در روز", "code": "TICKET_DAILY_LIMIT"}`
- Resource upload 6th per day: `{"message": "سقف روزانه 5 فایل", "code": "UPLOAD_DAILY_QUOTA", "retry_after": 86400}`
- Excel import 2nd per 10min: `{"message": "هر 10 دقیقه یک بار امکان ورود اکسل", "code": "IMPORT_RATE_LIMIT"}`
- Password reset 4th per hour per operator: `{"message": "حداکثر 3 بار بازنشانی رمز در ساعت", "code": "PASSWORD_RESET_RATE_LIMIT"}`

### 410 Gone - Old version file content hard deleted after 30d

```json
{
  "message": "فایل این نسخه حذف شده",
  "code": "FILE_CONTENT_DELETED",
  "scheduled_hard_delete_at": "2024-07-19T10:00:00Z"
}
```

### 500 Server Error - Unexpected

```json
{
  "message": "خطای سرور - لطفا بعدا تلاش کنید",
  "code": "SERVER_ERROR",
  "trace_id": "uuid-for-debugging-sentry"
}
```

- Always log full exception to laravel.log + Sentry optional, return generic message to user, not stack trace
- Include trace_id for debugging

### Frontend Handling

- 401 unauthenticated: Clear token, redirect /login, snackbar "نشست شما منقضی شد"
- 403 banned: Show banner "حساب شما بن شده: {reason}" + logout, 403 forbidden role: Show /403 page
- 404: Show /404 illustration "صفحه یافت نشد" + button go dashboard
- 409 conflict: Show conflict resolver modal Keep Server / Keep Mine / Merge with server_state diff
- 422 validation: Show inline field errors red under field + snackbar "اعتبارسنجی ناموفق"
- 429 rate limit: Snackbar red with message + show countdown retry_after seconds, disable button until retry_after
- 410 gone: Show "فایل این نسخه حذف شده" gray
- 500: Snackbar red "خطای سرور" + retry button + log to Sentry
- Offline: Red banner "آفلاین" + disabled buttons tooltip "برای این کار نیاز به اینترنت است" + queued actions pending badge in offline queue page
- Idempotent replayed: If response header X-Idempotent-Replayed true, show info "این درخواست قبلا انجام شده" but treat as success

### Persian Messages Must Be Exact as per FEATURES Docs

Use same Persian messages as defined in FEATURES docs for consistency, do not invent new Persian phrasing. For example:

- "نام کاربری یا رمز اشتباه است" (not "Invalid credentials")
- "حساب شما بن شده: {reason}"
- "رمز موقت منقضی شده - به IT مراجعه کنید"
- "تعداد تلاش‌ها زیاد - 15 دقیقه صبر کنید"
- "تداخل زمانی با {course} {day} {time}"
- "سقف روزانه 5 فایل"
- "حجم فایل حداکثر 50 مگابایت"
- "فرمت فقط PDF و DOCX"
- "حداکثر 3 تصویر"
- "حجم هر تصویر حداکثر 5 مگابایت"
- "تیکت بسته شده - تیکت جدید ثبت کنید"
- "این مشخصه حذف شده"
- "این جزوه حذف شده"
- "فایل این نسخه حذف شده"
- "کاربر یافت نشد یا دسترسی ندارید" (generic anti-enumeration)
- "برای نهایی‌سازی نیاز به اینترنت است"
- "مهلت نهایی‌سازی تمام شده - لیست موقت شما حذف شد"
- etc.

END ERROR HANDLING
