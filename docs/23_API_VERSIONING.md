# 23 - API Versioning Strategy - V9 Shared Host

## Current Version: v1

All endpoints from `06_API_OPENAPI.yaml` are `/api/v1/...`

- `/api/v1/auth/login`
- `/api/v1/specifications`
- `/api/v1/enrollment/temp`
- `/api/v1/resources`
- etc.

## Versioning Strategy

### URL Versioning (Chosen for Unify)

- Use URL path `/api/v1/` for current version, future `/api/v2/` for breaking changes
- Pros: Clear, cacheable, easy to route in Laravel `routes/api.php` via prefix `v1`, works on shared host cPanel .htaccess, no header negotiation
- Cons: URL duplication

### Implementation for v1 (Current)

In `routes/api.php`:

```php
Route::prefix('v1')->group(function() {
  Route::post('/auth/login', [AuthController::class, 'login']);
  Route::get('/specifications', [SpecificationController::class, 'index']);
  // ... all v1 routes
});
```

### When to Create v2?

Only for breaking changes:

- Changing request/response shape: e.g., `CourseSpecification` currently has `day_of_week` ENUM sat-sun-mon..., if you want to add `is_next_day` and change time handling for overnight, that's additive not breaking, can stay v1 with new optional field
- Breaking examples that require v2:
  - Change `academic_status_declared` from ENUM to object with history
  - Change `Resource` `file_path` from direct path to signed S3 URL
  - Change `Enrollment` status from ENUM temporary/finalized/archived to more states
  - Change authentication from Sanctum cookie to JWT only

For additive changes (new optional field, new endpoint, new filter param), stay v1 and document as minor version, no need v2.

### Deprecation Policy

- When v2 is created (e.g., in 1 year when scaling to 2000 students and moving to VPS with Redis), keep v1 running for 6 months with deprecation header `X-API-Deprecated: true` + `Sunset: Sat, 01 Jan 2025 00:00:00 GMT`
- Frontend PWA checks `X-API-Deprecated` header, shows banner "نسخه جدید API به زودی - لطفا اپ را بروزرسانی کنید"
- After 6 months, return 410 Gone for v1 with message "نسخه 1 منسوخ شده - لطفا به نسخه 2 مهاجرت کنید" and link to migration guide

### No Header Versioning

- Do NOT use header `Accept: application/vnd.unify.v1+json` - harder to test via browser and cURL, not cacheable, not visible in URL, shared host LiteSpeed may strip custom Accept header
- Use URL versioning only for simplicity

### Version in Health Endpoint

`GET /api/v1/health` returns:

```json
{
  "status": "ok",
  "version": "9.0.0",
  "api_version": "v1",
  "mode": "online"
}
```

Frontend can check `api_version` and show update banner if backend has v2 and frontend still uses v1.

### For 600 Students MVP

- Stay v1 only, no v2 needed
- If you need to add overnight support `is_next_day` BOOL, it's additive, stay v1, add optional field `is_next_day` default false, no breaking
- If you need to add `academic_status_at_enrollment` to enrollments (FIX C2), it's additive, stay v1

END API VERSIONING
