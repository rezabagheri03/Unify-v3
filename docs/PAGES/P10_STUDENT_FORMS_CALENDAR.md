# P10 Student Forms, Academic Calendar, NoticeBoard, FAQ - V9 Shared Host

## Forms Repository Page /forms
UI Tabs Department Forms own dept + University Forms Search bar title List Each form card Title bold Description truncated Signature guide badge "راهنما: امضا مدیر گروه + مهر آموزش" with icon info Download button File size Date Shamsi, Click download GET /api/v1/forms/{id}/download direct file /uploads/forms/{dept}/{uuid}.pdf not signed S3, download manager via Cache API saves to cache dir, offline indicator cloud not cached check cached, Data GET /api/v1/forms?department_id=&is_university_level=&search=&page= Workbox cache 1h

## Academic Calendar Page /calendar
UI View Toggle Timeline / Calendar, Timeline View Horizontal scrollable cards sorted start asc clickable date cards color badge per event_type title description truncated start-end Shamsi countdown "5 روز مانده" for upcoming "در حال برگزاری" current "پایان یافته" past, Calendar View Jalali month/year navigation grid days dots colored for events that day click day shows events list that day bottom sheet, Filters University-wide vs Department Event Type multi-select, Detail Modal Title Description Start/End Shamsi+Gregorian Event Type badge color Countdown Related action button If registration_open "رفتن به ثبت‌نام" navigates scheduler Phase A If exam_period_start "مشاهده امتحانات" navigates exam, Integration banner If calendar says registration close passed but global_state still enrolling show warning to Admin only student normal, Data GET /api/v1/academic-calendar?semester=current&department_id=&is_university_wide=&event_type=&page= Workbox cache 1h

## NoticeBoard Page per Spec /notices/{specId}
UI AppBar Course Name + Professor List active notices sorted priority high first then newest Each card Title Content Priority badge color Banner color preview Created Shamsi Expires countdown if set Expired badge if expired filtered out by default but toggle "نمایش منقضی شده‌ها", Offline cached Workbox, Data GET /api/v1/specifications/{specId}/noticeboard?active_only=true

## FAQ Page per Spec /faq/{specId}
UI List FAQ sorted pinned first accordion Question bold clickable expands Answer Pinned badge "سنجاق شده" yellow Search Q/A Offline cached Workbox, Data GET /api/v1/specifications/{specId}/faq

## Common V9
All pages pull to refresh calls polling endpoint, offline banner if offline, polling for calendar updates via GET /api/notifications/unread every 15s
