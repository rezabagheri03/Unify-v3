# P01 Auth Pages - V9 Shared Host (Laravel Sanctum)

## /login
UI Centered card Logo Brand Unify Persian title "ورود به سامانه یکپارچه دانشگاه", Form Username Student Number/Personnel ID placeholder, Password show/hide eye, Button "ورود" loading, Link "رمز را فراموش کرده‌اید؟" modal "برای بازیابی رمز به صورت حضوری به IT با کارت شناسایی مراجعه کنید" + address, Theme toggle dark mode allowed even login, Error messages generic "نام کاربری یا رمز اشتباه است" 401, "حساب شما بن شده: {reason}" 403 banned, "رمز موقت منقضی شده - به IT مراجعه کنید" 403 expired, "تعداد تلاش‌ها زیاد - 15 دقیقه صبر کنید" 429 rate limit via Laravel throttle file cache, Captcha after 3 fails optional
API POST /api/v1/auth/login {username, password} returns Sanctum token + user, States Idle Loading Error Success redirect onboarding if must_change_password or first_name null else dashboard, Offline login requires online, if offline show banner "برای ورود نیاز به اینترنت است" but allow cached offline via IndexedDB? No, require online for auth, after first login cached data allows viewing dashboard offline

## /onboarding
Stepper 2 steps Personal Info + Password Change, Step1 First Name Last Name required Supplementary Details optional textarea, Step2 Old Password temp New Password Confirm New Password Complexity indicator live min8 upper lower number special not same old not in last 3, Checkbox "متوجه شدم باید رمز را تغییر دهم", Button "ثبت و ادامه" disabled until valid, Error "رمز جدید نمی‌تواند با موقت یکسان باشد"
API POST /api/v1/onboarding {first_name, last_name, supplementary_details} + POST /api/v1/password/change {old, new}, States success redirect dashboard

## /settings/password Change Password Page
Form Old Password New Password Confirm Complexity indicator Checkbox "خروج از سایر دستگاه‌ها" default true, Button "تغییر رمز" success snackbar "رمز با موفقیت تغییر کرد", API POST /api/v1/password/change

## IT Envelope PDF
Content University logo Title "پاکت رمز موقت سامانه Unify" Student Name ID Username Temp Password large monospace QR code username+temp Instructions Persian Printed date Operator name Warning "این برگه را نزد خود نگه دارید - 7 روز اعتبار" Format A5 printable Generated on-fly dompdf Laravel not stored, contains QR

## Edge V9
Student tries access /dashboard without onboarding redirect /onboarding guard, Temp expired during onboarding error 403 redirect forgot info modal, Password change old same as new error
