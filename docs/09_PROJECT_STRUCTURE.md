# 09 - Project Structure - V9 Shared Host Ready

This doc defines exact folder structure an agentic LLM must create for both backend Laravel and frontend React, matching deployment guide for Pars Pack Cloud Host + Host Iran.

## Root Structure (Local Dev)

```
unify-project/
  README.md
  backend/ (Laravel - will be deployed to /home/username/unify-backend outside public_html)
    app/
      Console/
        Commands/
          EnrollmentsWipeGrace.php
          TicketsEscalate.php
          CalendarWarn.php
          ResourcesCleanupOldVersions.php
          FilesLruCleanup.php
      Http/
        Controllers/
          Api/
            Auth/
              AuthController.php (login, logout)
              OnboardingController.php
              PasswordController.php
            UserController.php (profile, academic-status)
            CourseController.php
            CourseSpecificationController.php
            EnrollmentController.php (temp add/remove, final)
            GoldenScheduleController.php (backtracking)
            ResourceController.php (list, detail, download, upload, new-version, approve, reject, hard-delete, pending)
            RatingController.php
            StickyNoteController.php
            MessageController.php (inbox, send, edit, delete, read)
            TicketController.php (list, create, detail, reply, status, assign)
            Curriculum/
              CurriculumChartController.php (list, create, update, submit, approve, reject)
              PassedCourseController.php (list, store)
            FormController.php
            AcademicCalendarController.php
            NoticeBoardController.php
            FaqController.php
            AssignmentController.php (list, create, update, delete, submit, grade, download)
            SemesterController.php (list, current, set-current, set-global-state)
            Admin/
              UserController.php (search, ban/unban)
              BrandingController.php (logo upload)
              ResourceController.php (final approval, hard delete)
            Owner/
              UserController.php (manual add, bulk import, reset password envelope)
              AuditLogController.php (list, detail, export)
              AnalyticsController.php
              HonorFlagController.php
            NotificationController.php (unread polling, mute)
            DeviceController.php (register Pushe token)
            Excel/
              ImportController.php (users, courses, specs, curriculum, calendar)
              ExportController.php (same)
            FileCacheController.php (pin/unpin local cache meta)
        Middleware/
          AuditLogMiddleware.php
          RoleMiddleware.php (checks role matrix + dept scope)
          ThrottleRequestsCustom.php (file cache based)
        Requests/
          StoreSpecificationRequest.php (validation day_of_week, time_end > time_start, etc.)
          StoreResourceRequest.php (file mime finfo, max 50MB)
          StoreTicketRequest.php (images max 3x5MB)
      Models/
        User.php (with casts, relations, hasMany enrollments, etc.)
        Department.php
        Semester.php
        Course.php
        CourseSpecification.php (with day_of_week enum)
        StudentPassedCourse.php
        Enrollment.php
        Resource.php (with family_id, previous_version_id, is_superseded, scheduled_hard_delete_at)
        ResourceRating.php
        ResourceStickyNote.php
        ResourceUploadCount.php
        Message.php
        MessageReadStatus.php
        Ticket.php
        TicketReply.php
        TicketDailyCount.php
        AssignmentTracker.php
        CurriculumChart.php
        Faq.php
        NoticeBoard.php
        Form.php
        AcademicCalendar.php
        DeviceToken.php
        AuditLog.php
        IdempotencyKeys.php
        Notification.php
        NotificationMute.php
        SystemConfig.php
        HonorFlag.php
        GoldenScheduleCache.php
        PasswordHistory.php
      Services/
        PusheService.php (Guzzle curl to https://api.pushe.co/v2/ - send push)
        KavenegarService.php (Guzzle curl to Kavenegar SMS API)
        ShamsiService.php (Morilog\Jalali wrapper: toGregorian, toShamsi, isValid)
        FileCacheService.php (local filesystem LRU 10GB Shop limit cleanup)
        HonorFlagService.php (check final_semester abuse >2)
      Console/
        Kernel.php (schedule commands everyMinute, hourly, daily)
      Policies/
        CourseSpecificationPolicy.php (dept scope)
        ResourcePolicy.php (own dept)
        TicketPolicy.php (own dept or assigned)
    routes/
      api.php (all endpoints from OpenAPI spec)
    database/
      migrations/ (15 files from 07_DATABASE_MIGRATIONS/)
      seeders/
        OwnerSeeder.php (creates owner user with temp password)
        DepartmentSeeder.php (CS, etc.)
        CourseSeeder.php (40 courses for 600 students)
        SemesterSeeder.php (current semester 1403-1 enrolling)
        SystemConfigSeeder.php (brand_name Unify, logo_path)
    storage/
      app/
        public/
          resources/{course_id}/{professor_id}/{uuid}.pdf (final)
          forms/{dept}/{uuid}.pdf
          branding/logo.png
          assignments/{student_id}/{uuid}.pdf
          temp/ (staging for pending resources)
    config/
      filesystems.php (public disk root /home/username/public_html/uploads for shared host)
      auth.php (Sanctum)
    .env.example (from 08_ENV_EXAMPLE.md)
    composer.json (laravel 10, morilog/jalali, phpoffice/phpspreadsheet, tymon/jwt-auth or sanctum, intervention/image, simple-qrcode, dompdf)

  frontend/ (React PWA - built static to public_html)
    public/
      manifest.json (PWA manifest name Unify, theme_color #1976D2, RTL)
      logo.png
    src/
      api/
        client.ts (Axios with baseURL VITE_API_URL, interceptor adds Sanctum CSRF token X-XSRF-TOKEN, Idempotency-Key header UUID v4 for mutating requests, retry)
        syncQueue.ts (idb-keyval wrapper for IndexedDB queue pending/synced/failed/conflict)
        polling.ts (useNotificationsPolling hook: setInterval 15s foreground 60s background GET /api/notifications/unread?since=, calls Pushe if needed)
      db/
        idb.ts (idb-keyval wrapper for syncQueue, fileCacheMeta, userPreferences, notificationMuteLocal)
      stores/
        authStore.ts (Zustand + persist idb-keyval, user, token, must_change_password, Honor status)
        schedulerStore.ts (temp list, finalized, archive, golden suggestions)
        resourceStore.ts (list, detail, cache status)
        notificationStore.ts (unread count, polling status isOnline isIntranetMode)
      components/ (from P18)
        CourseCard.tsx (header colored hash professor_id, day+time, location, credits, exam date, notice banner, footer Download Resources Class Group Details, context menu mute)
        FileCard.tsx (icon PDF/DOCX, title author Shamsi date rating avg download count badge professor/expert/admin version cache status cloud/check pin)
        SearchBar.tsx (debounce 300ms)
        ShamsiDatePicker.tsx (react-datepicker Jalali)
        Timeline.tsx (Academic Calendar horizontal cards + Jalali month calendar dots)
        FlipCard.tsx (Framer Motion exam flip with reduced motion fallback)
        Banner.tsx (CriticalBanner red, Warning yellow, GraceCountdown live second, IntranetBadge yellow, OfflineBadge red, HonorBanner yellow)
        MessageRow.tsx, TicketRow.tsx, KanbanColumn.tsx, AssignmentCard.tsx, RatingStars.tsx, StickyNoteEditor.tsx, FileUploadDropzone.tsx (drag-drop 50MB PDF/DOCX, validation finfo client-side), OfflineQueueRow.tsx, ThemePreview.tsx, ConfirmationModal.tsx (requireTyping for dangerous actions), EmptyState.tsx, Skeletons (CourseCardSkeleton, etc.)
      screens/
        Auth/ Login.tsx, Onboarding.tsx
        Student/ Dashboard.tsx (P02), SchedulerA.tsx (P03), SchedulerB.tsx (P04), SchedulerCExamFlip.tsx (P05), ResourceHubList.tsx + Detail.tsx + Upload.tsx + MyUploads.tsx (P06), InboxList.tsx + DetailThread.tsx (P07), TicketingList.tsx + Detail.tsx + New.tsx (P08), CurriculumCharts.tsx (P09), FormsCalendar.tsx (P10 includes Forms, Calendar, NoticeBoard, FAQ tabs), AssignmentTrackerList.tsx + Detail.tsx + New.tsx (P11), Settings/ Theme.tsx, Notifications.tsx, Profile.tsx, Password.tsx, OfflineQueue.tsx, IntranetStatus.tsx, About.tsx (P12)
        Professor/ Dashboard.tsx + StudentsList.tsx + ResourcesList.tsx + UploadCenter.tsx + Messages.tsx + NoticeBoardCRUD.tsx + FaqCRUD.tsx (P13)
        Expert/ Dashboard.tsx + CoursesCRUD.tsx + SpecificationsCRUD.tsx + ImportExcel.tsx + PrereqManager.tsx + CurriculumEditor.tsx + FormsManagement.tsx + TicketsHelpDesk.tsx + TargetedMessaging.tsx + PendingResources.tsx + ExcelImportExport.tsx (P14)
        Head/ Dashboard.tsx + FinalChartApprovalQueue.tsx + ProfessorOversight.tsx (P15)
        Admin/ Dashboard.tsx + SemestersManagement.tsx + UsersManagement.tsx + TicketsEscalated.tsx + BrandingLogo.tsx + FormsUniversity.tsx + CalendarUniversity.tsx + ResourcesFinalApproval.tsx + MessagingUniversity.tsx + AnalyticsLimited.tsx + ExcelImportExport.tsx (P16)
        Owner/ Dashboard.tsx + ManualAddUser.tsx + BulkImport.tsx + ResetPasswordEnvelope.tsx + AuditLogsViewer.tsx + AnalyticsFull.tsx + SystemReadOnlyView.tsx (P17)
      utils/
        shamsi.ts (date-fns-jalali wrapper: toShamsi, toGregorian, format Shamsi YYYY/MM/DD, isValid)
        validators.ts (credit limits per honor status, time overlap day_of_week, exam overlap, prereq warning)
        honor.ts (abuse detection final_semester >2)
        idempotency.ts (generate UUID v4)
      platforms/
        web/ manifest.json, sw.js Workbox runtime caching for GET specs/enrollments/resources metadata/curriculum/calendar/forms, Cache API LRU 100MB for file content
        android/ capacitor.config.json server.url https://unify-cs.ac.ir, plugins filesystem local-notifications pushe-capacitor
      App.tsx (routes from P00 sitemap, auth guards must_change_password onboarding, role guard 403, grace guard, theme provider MUI RTL)
      main.tsx

## Shared Host Deployment Structure (cPanel)

```
/home/username/ (cPanel home)
  /unify-backend/ (Laravel outside public_html for security)
    /app/...
    /routes/api.php
    /database/migrations/ (15 files)
    .env (filled from 08_ENV_EXAMPLE)
    composer.json
    vendor/ (composer install --no-dev)
    storage/app/public/ -> symlink to /home/username/public_html/uploads (created via php artisan storage:link or manually ln -s)
  /public_html/ (React build + Laravel public for api subdomain OR combined)
    /index.html (React PWA)
    /assets/ (React JS/CSS)
    /uploads/
      /resources/{course_id}/{professor_id}/{uuid}.pdf (final approved)
      /forms/{dept}/{uuid}.pdf
      /branding/logo.png
      /assignments/{student_id}/{uuid}.pdf
    /app.apk (Android direct download optional)
    /.htaccess (SPA fallback + api proxy if api in same domain)
    /api/ (if using subfolder for Laravel public, contains Laravel public/index.php)
  If two hosts:
    Host Iran (https://unify-cs.ac.ir) public_html -> React build only
    Cloud Host (https://api.unify-cs.ac.ir) public_html/api -> Laravel public
```

## Naming Conventions

- Models PascalCase singular: `CourseSpecification`, `StudentPassedCourse`
- Controllers PascalCase + Controller: `EnrollmentController`, `GoldenScheduleController`
- Routes kebab-case: `/api/v1/course-specifications`, `/api/v1/enrollment/temp`
- Database tables snake_case plural: `course_specifications`, `student_passed_courses`, `idempotency_keys`
- Frontend components PascalCase: `CourseCard.tsx`, `FileCard.tsx`
- Stores camelCase: `authStore.ts`, `schedulerStore.ts`
- API client camelCase: `client.ts`, `syncQueue.ts`

## No Docker, No Redis, No Postgres, No MinIO - Shared Host Only

- Cache driver file (not Redis), or memcached if Cloud Host provides (check cPanel -> Select PHP Version -> extensions memcached)
- Queue connection database (not Redis), jobs run via cron schedule:run
- Filesystem disk public local, not s3
- No separate WebSocket server, polling only

END PROJECT STRUCTURE
