# F18 Theme Branding UI/UX - V9 Shared Host (Same Frontend)

## Purpose
Uploadable logo, configurable brand name Unify, Persian RTL, MUI themes, dark mode, exam flip animation - Frontend same as V7/V8, no change, backend now Laravel

## Branding

### Logo Upload Admin
Page Base System Management, Form File PNG/SVG max 2MB dimensions max 512x512 suggested sanitized For SVG remove script tags via HTMLPurifier, For PNG check magic finfo, Preview current logo + new logo preview, Storage /uploads/branding/logo + thumbnails 128,64,32, On save Updates SystemConfig table key logo_path value /uploads/branding/logo.png invalidates cache AuditLog major_edit polling to all clients reload logo via ?v=timestamp

### Brand Name
SystemConfig key brand_name default "Unify" editable Admin max 50 chars Persian/English

### Themes MUI Theme Provider
ThemeProvider with ThemeOptions per theme palette primary secondary background text, RTL plugin stylis-plugin-rtl + CacheProvider emotion, 5 preset themes Unify Blue default primary #1976D2 secondary #9C27B0 Forest Green primary #2E7D32 Sunset Orange primary #EF6C00 Midnight Dark dark mode variant High Contrast accessibility, Each theme light and dark variants

### Theme Selection
Settings page Theme dropdown presets color preview circles selected checkmark name, Dark Mode toggle switch, Department Default info "تم پیش‌فرض دانشکده شما: {theme} - شما می‌توانید تغییر دهید", Preview area sample CourseCard with selected theme, Save POST /api/v1/users/me/preferences {theme_id, dark_mode} via Laravel, Offline theme saved locally IndexedDB immediate

### Dark Mode
Toggle all roles header moon/sun icon, MUI palette mode dark background #121212, Persists UserPreferences table

### Exam Mode Flip Animation V9 Same
Component FlipCard Framer Motion Front weekly timetable Sat-Wed Thu/Fri optional Back linear exam list sorted final Gregorian asc, Flip trigger button "مشاهده برنامه امتحانات" front->back, button text toggles "بازگشت به برنامه هفتگی" back->front, Animation rotateY 0->180 0.6s easeInOut perspective 1000 preserve-3d, Reduced Motion: useReducedMotion hook if prefers-reduced-motion reduce use fade opacity not rotateY via MotionConfig reducedMotion="never"? Actually if prefers-reduced-motion reduce fade 0.3s, Flip state local isFlipped bool

### UI Components Persian RTL Same as V7

#### Course Card
Custom colored header background deterministic hash professor_id hue HSL 70% saturation 50% lightness text white WCAG AA check luminance, Body Course Name Bold 16px Professor 14px Day+Time 13px Icon clock Location Icon Credits badge Exam date small, Notice Banner if active high priority first notice title priority color, Footer Action Buttons 3 Download Resources icon download Class Group icon telegram Details icon info -> Details modal, Context menu 3 dots Mute/unmute notifications for this spec Remove? No remove only via scheduler if Phase A, Conflict red border warning icon tooltip, Archived gray overlay 0.5 + badge آرشیو, Muted bell off, Skeleton variant loading, RTL

#### File Card
Icon left PDF red DOCX blue, Right content title bold 14px author 12px gray date 12px calendar icon rating row star avg count download count badge type color version badge cache status cloud/check pin icon, Actions Download Pin More menu Skeleton

#### Other Components Same
SearchBar debounce 300ms Persian placeholder "جستجو نام یا کد درس", ShamsiDatePicker wraps react-datepicker Jalali locale fa-IR month names فروردین, Timeline horizontal scrollable cards color badge title countdown, Banners Critical red Warning yellow Info blue Success green GraceCountdown live second green>6h orange 2-6h red<2h pulse IntranetBadge yellow wifi off OfflineBadge red OnlineBadge green HonorBanner yellow

#### Accessibility
Keyboard navigable tabIndex aria-label Persian role focus outline Contrast WCAG AA Reduced Motion support via useReducedMotion hook
