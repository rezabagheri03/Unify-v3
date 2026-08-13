# P05 Student Scheduler Phase C Exam Flip - V9 Shared Host

## Route /scheduler/exam
Trigger Button "مشاهده برنامه امتحانات" appears when global_state exam OR AcademicCalendar exam_period_start within 14 days OR always visible but disabled tooltip, Access allowed anytime but if not exam period empty state "هنوز فصل امتحانات شروع نشده"

## Layout Flip Animation Same
Container FlipCard Framer Motion Front weekly timetable same Phase B, Back linear exam list sorted final Gregorian asc, Flip trigger Button "مشاهده برنامه امتحانات" when front flips to back button text "بازگشت به برنامه هفتگی" when back flips to front, Animation rotateY 0->180 0.6s easeInOut perspective 1000 preserve-3d, Reduced Motion If prefers-reduced-motion fade opacity instead rotateY

## Back View Exam List Details
List items Each row card Course Name Bold Professor Code Final Exam Date Shamsi+Day Persian e.g., شنبه 1403/04/20 + Time + Location Midterm If exam_date_midterm exists orange badge "میان‌ترم" + date Shamsi location same, Color Final blue badge Midterm orange, Countdown "5 روز مانده" upcoming, Sort By final exam date asc midterm within same course below final, Group by date optional

## Data Fetching V9
Same enrollments finalized specs include exam dates Gregorian + Shamsi original GET /api/v1/enrollments?semester=current&status=finalized includes exam dates via expand, Cached Workbox

## States
Loading skeleton front/back, Empty No exams If specs have no exam dates set "تاریخ امتحان برای دروس شما هنوز ثبت نشده", Offline cached exam list viewable

## Edge V9
Spec has no final exam date null "تاریخ نهایی ثبت نشده" gray, Many exams 20+ back list scrollable max height 80vh, Flip during loading disable flip button until loaded, Rapid double click debounce 600ms, Exam date changes polling push notification to enrolled "تاریخ امتحان {course} تغییر کرد" via polling 15s + Pushe PHP curl
