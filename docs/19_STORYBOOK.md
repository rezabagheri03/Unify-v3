# 19 - Storybook - Component Library - V9 Shared Host

## Purpose
For agentic LLM to build common components from P18 in isolation and test visually before integrating into pages. Storybook is not deployable on shared host, only for local dev.

## Setup

```bash
# Frontend
cd frontend
npm install -D @storybook/react @storybook/react-vite @storybook/addon-essentials @storybook/addon-interactions @storybook/addon-a11y
npx storybook@latest init
# Configure .storybook/main.ts with Vite
# Add addons: essentials, interactions, a11y (for WCAG checks)
```

## Stories to Create (From P18 Common Components)

### CourseCard
- Story: Default, With Notice Banner, Conflict Red Border, Archived Gray Overlay, Muted Bell Off, Skeleton Loading
- Props: courseName, courseCode, professorName, professorId, dayOfWeek, timeStart, timeEnd, location, credits, examFinalDate, noticeBoard first active, custom header color hash professor_id, onDownloadResources, onClassGroup, onDetails, onMuteToggle, isMuted, isArchived, isConflict
- Tests: Header color deterministic hash professor_id hue, contrast WCAG AA check luminance, Footer 3 buttons Download Resources navigates /resources?course_id=&professor_id=, Class Group opens external browser confirmation dialog, Details opens modal

### FileCard
- Story: Default PDF, DOCX, With Badge Professor/Expert/Admin, Version, Cache Status Cloud/Check, Pinned, Download Count High, Rating High, Skeleton
- Props: fileType PDF/DOCX, title, author, shamsiDate, averageRating excluding self, ratingCount, downloadCount, badgeType, version, cacheStatus, isPinned, isProtected, onDownload, onPinToggle, onClick
- Tests: Icon PDF red DOCX blue, Title bold, Rating star + avg + count, Download button triggers GET /api/v1/resources/{id}/download + Cache API save

### SearchBar
- Story: Default, With Filters Chips, With Value + Clear Button, Debounce 300ms

### ShamsiDatePicker
- Story: Default, With Value Shamsi YYYY/MM/DD, Invalid Shamsi 1403/13/40 error Persian, Min Max Date
- Tests: Converts Shamsi to Gregorian via date-fns-jalali for API

### Timeline Academic Calendar
- Story: Timeline View Horizontal scrollable cards, Calendar View Jalali month grid dots colored, Click day bottom sheet
- Props: events array id title desc startGregorian endGregorian shamsiStart shamsiEnd eventType colorCode viewMode timeline/calendar onEventClick

### FlipCard Exam Mode
- Story: Front Weekly Timetable, Back Linear Exam List, Is Flipped, Reduced Motion fallback fade
- Tests: RotateY 0->180 0.6s easeInOut perspective 1000, Reduced Motion If prefers-reduced-motion fade opacity not rotateY

### Banners
- Story: CriticalBanner Red, Warning Yellow, Info blue, Success green, GraceCountdown live second green>6h orange 2-6h red<2h pulse, IntranetBadge yellow wifi off, OfflineBadge red, OnlineBadge green, HonorBanner yellow

### MessageRow, TicketRow, Kanban Column, AssignmentCard, Rating Stars, Sticky Note Editor, File Upload Dropzone, Offline Queue Row, Theme Preview, Confirmation Modal, Empty State, Skeletons

- Each with Default, With Data, Loading, Error, Empty, Mobile, Desktop stories

## Accessibility Addon (a11y)

- For each story, run a11y addon checks WCAG AA contrast, keyboard navigable, aria-label Persian, role, focus visible outline
- Fix: CourseCard header color hash hue must have contrast white text WCAG AA, check luminance, if fails darken color

## How to Use Storybook for LLM

1. Build component in isolation in `src/components/CourseCard.tsx` with props
2. Create story `src/components/CourseCard.stories.tsx` with args for each variant
3. Run `npm run storybook` -> open http://localhost:6006 -> visually check component in different states
4. Run a11y checks via addon, fix contrast, keyboard
5. Then integrate into pages P02-P18

## Not Deployable on Shared Host

Storybook is dev only, not deployed to Pars Pack Cloud Host. Only React build `dist/` is deployed to `public_html`.

END STORYBOOK
