# F15 Notification Infrastructure - V9 Shared Host - Intranet Must-Have via Polling + Pushe PHP

## Requirement Kept Must Have
Must work when international internet cut, national intranet only, student can mute/unmute per spec

## Architecture V9 Shared Host (No WebSocket, Polling + Pushe PHP)

### Tier 1 Polling Primary (Works Without Outside Internet, Works on Shared Host)
- No internal WebSocket server (was separate service, removed to fit shared host cPanel)
- Frontend polls GET /api/notifications/unread?since=last_timestamp every 15s foreground, 60s background via setInterval + Workbox Background Sync, works on intranet because HTTP to Iranian IP works even when international cut
- Backend stores notifications in MySQL table Notification id user_id type title body data JSON priority critical/high/low read BOOL created_at, unread count = count where read=0
- When event happens (spec updated, new resource approved, ticket replied) Laravel creates Notification rows for affected users and also calls Pushe if needed
- Polling endpoint returns unread notifications since timestamp, client shows banner + updates inbox badge

### Tier 2 Android Pushe via PHP Curl (Works Without Google FCM, Works on Shared Host)
- Provider Pushe.ir Iranian push provider has Iranian servers reachable via intranet unlike FCM which needs google.com
- SDK pushe-capacitor for Android APK, app on launch registers Pushe token POST /api/v1/devices {token, provider=pushe, platform=android}
- Server: When notification event, checks DeviceToken where provider=pushe and is_active and user_id in target, sends via Pushe API HTTP via Guzzle curl in Laravel (API endpoint https://api.pushe.co/v2/ reachable via Iranian IP, even on shared host curl works), fallback to FCM if Pushe fails
- Android can receive push even when app killed/background due to Pushe service

### Tier 3 iOS Removed Entirely (User Request)
- No iOS app, per user request forget iOS totally, at most Android app will be created, so no APNs needed

### Tier 4 SMS Fallback Optional (Works on Intranet via Local Telco)
- For CRITICAL events only (spec time/location change, grace period ending <2h, ticket answered, registration close 24h) if user has mobile and opted-in SMS notifications in settings, send SMS via Kavenegar or Melipayamak API via PHP curl (Kavenegar has Iranian servers), configurable per event type in SystemConfig, student opt-in in settings "دریافت SMS برای اطلاعیه‌های حیاتی"
- SMS works via SS7 not IP, so works during intranet

### Intranet Detection (Polling Mode)
- Client periodically checks GET /api/v1/health internal (unify.domain) + fetch https://8.8.8.8 or https://google.com no-cors, If internal reachable but external not -> isIntranetMode=true, App state isOnline, isIntranetMode, isOffline, UI Badge top header shows "حالت اینترانت" yellow if intranet, "آفلاین" red if offline, "آنلاین" green if online
- When isIntranetMode true increase polling to 10s, show info "اینترنت بین‌الملل قطع است - اعلان‌ها از طریق سرور داخلی"

## Mute Per Spec
- Table NotificationMute user_id+specification_id UNIQUE muted BOOL, UI Settings and per course card toggle "بی‌صدا کردن اعلان‌های این درس" POST /api/v1/notifications/mute {specification_id, muted}, Server checks mute before sending: If muted true for user+spec skip push/polling for that spec's events except critical? For critical bypasses mute, high/low respects mute
- Local cache IndexedDB NotificationMuteLocal for offline check

## Event Types Priority Channels V9

| Event | Priority | Channels V9 | Message |
| :--- | :--- | :--- | :--- |
| spec time/location changed/cancelled | critical | Polling + Pushe + SMS if opted + local | "زمان درس {course} تغییر کرد" |
| schedule conflict due to spec change | critical | Polling + Pushe + local | "تداخل برنامه به دلیل تغییر {course}" |
| grace period ending <2h | critical | Polling + Pushe + SMS + local scheduled | "2 ساعت تا پایان مهلت نهایی‌سازی" |
| new resource enrolled | high | Polling + Pushe | "جزوه جدید برای {course}: {title}" |
| ticket answered | high | Polling + Pushe + SMS optional | "پاسخ جدید برای تیکت شما" |
| ticket escalated | high | Polling + Pushe to admin | "تیکت {id} اسکلیشن شد" |
| registration open 7-day warning | high | Polling + Pushe + local scheduled | "7 روز تا شروع ثبت‌نام" |
| registration close 24h warning | critical | Polling + Pushe + SMS + local | "24 ساعت تا پایان ثبت‌نام" |
| exam period start | high | Polling + Pushe + local | "فصل امتحانات شروع شد" |
| assignment deadline reminder | high | Local scheduled + Polling + Pushe | "فردا سررسید تکلیف {title}" |
| assignment graded | high | Polling + Pushe | "تکلیف {title} نمره {grade}" |
| notice high priority | high | Polling + Pushe | "اطلاعیه جدید {course}: {title}" |
| general low | low | Polling only | "پیام جدید از آموزش" |

## API Laravel V9
GET /api/v1/notifications/unread?since=timestamp
POST /api/v1/notifications/mute {specification_id, muted}
GET /api/v1/notifications/mutes
POST /api/v1/devices {token, provider=pushe, platform=android}
DELETE /api/v1/devices/{token}
GET /api/v1/health (for intranet detection)

## Payload
{id UUID, type enum, title, body, data JSON {spec_id, course_id, resource_id, ticket_id}, priority, created_at_g, shamsi_formatted, is_muted bool}

## Offline Queue
Notifications stored DB Notification user_id type title body data priority read BOOL created_at, unread count count where read false, When offline queued in server DB, when client polling gets unread

## Edge
Polling disconnected + offline -> local notifications still fire scheduled, Muted but critical bypasses mute, Pushe token expired is_active false fallback to polling next foreground, SMS fails log but don't block
