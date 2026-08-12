# P18 Common Components - V9 Shared Host (React PWA Static)

## Purpose Reusable components across all pages consistency - No change from V7/V8 except file cache notes for 10GB limit

## CourseCard V9 Same
Props courseName courseCode professorName professorId dayOfWeek timeStart timeEnd location credits examFinalDate Shamsi noticeBoard first active custom header color hash professor_id hue HSL 70% saturation 50% lightness text white WCAG AA, Body Day badge clock Location Credits badge Exam date small, Notice Banner if active high priority, Footer 3 buttons Download Resources Class Group Details modal, Context menu Mute/unmute, Conflict red border warning icon tooltip, Archived gray overlay 0.5 badge آرشیو, Muted bell off, Skeleton variant loading, RTL

## FileCard V9 Same but local filesystem note
Props fileType PDF/DOCX title author shamsiDate avgRating ratingCount downloadCount badgeType professor/expert/admin version cacheStatus cached/not cached via Cache API isPinned isProtected onDownload onPinToggle onClick, Layout left icon 48px PDF red DOCX blue content title bold author date rating row star avg count download badge type color version badge cache status cloud/check pin icon, Actions Download Pin More menu Skeleton

## SearchBar
Props placeholder Persian value onChange debounced 300 onClear filters slot sort slot UI TextField search icon left clear right filters chips Debounce hook

## ShamsiDatePicker
Props value Gregorian Date or Shamsi string YYYY/MM/DD onChange returns Gregorian ISO + Shamsi string label Persian required minDate maxDate disabled Implementation wraps react-datepicker Jalali locale fa-IR month names فروردین year 1300-1500 format YYYY/MM/DD Validation isValidJalaali error Persian Converts to Gregorian via Morilog\Jalali PHP and date-fns-jalali JS for API

## Timeline Academic Calendar
Props events id title desc startGregorian endGregorian shamsiStart shamsiEnd eventType colorCode viewMode timeline/calendar onEventClick Timeline horizontal scrollable cards color badge title date countdown Calendar Jalali month grid dots colored click bottom sheet

## FlipCard Exam Mode
Props frontNode weekly timetable backNode exam list isFlipped onFlip reducedMotion Implementation Framer Motion motion.div rotateY perspective 1000 preserve-3d backface hidden front back absolute reduced fade opacity not rotateY via MotionConfig, Flip state local isFlipped bool

## Banner Components
CriticalBanner Red #FFEBEE red border icon warning title body action button optional closeable, Warning Yellow Info blue Success green, GraceCountdown live second green>6h orange 2-6h red<2h pulse, IntranetBadge yellow wifi off Polling badge blue "حالت نظرسنجی هر ۱۵ ثانیه" OfflineBadge red OnlineBadge green HonorBanner yellow

## MessageRow, TicketRow, Kanban Column, AssignmentCard, Rating Stars, Sticky Note Editor, File Upload Dropzone, Offline Queue Row, Theme Preview, Confirmation Modal, Empty State, Skeleton Variants Same as V7/V8

### File Upload Dropzone V9 Shared Host Note
Props accept PDF/DOCX or images or any maxSize 50MB multiple onFileSelect current file preview UI drag-drop dashed border icon upload text file name size mime preview validation error red remove, Note 10GB Shop limit warning "فضای هاست محدود است - حداکثر ۱۰ گیگ"

### Offline Queue Row V9
Props entityType icon action summary created Shamsi status badge pending/syncing/synced/failed/conflict attempts lastError onRetry onDelete onResolve status colors pending orange syncing blue spinner synced green failed red conflict yellow, Note MySQL idempotency not Redis, Polling not WebSocket

### Accessibility
Keyboard navigable tabIndex aria-label Persian role focus outline Contrast WCAG AA Reduced Motion useReducedMotion hook

END P18 V9
