import React, { Suspense, lazy } from 'react';
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import { Box, CircularProgress, CssBaseline } from '@mui/material';
import { CacheProvider } from '@emotion/react';
import createCache from '@emotion/cache';
import { prefixer } from 'stylis';
import rtlPlugin from 'stylis-plugin-rtl';
import { useAuthStore } from './stores/authStore';
import { ErrorBoundary } from './components/ErrorBoundary';
import { ProtectedRoute } from './components/ProtectedRoute';
import Layout from './components/Layout';
import { homePathFor } from './utils/navigation';

// Auth (kept eager: they are the first paint)
import Login from './screens/Auth/Login';
import Onboarding from './screens/Auth/Onboarding';

// PERF-05 fix: every role area is a lazy chunk. Students no longer download
// Owner/Expert/Admin tooling (the entire app previously shipped as one eager
// ~586 kB-raw bundle to every phone).
const Dashboard = lazy(() => import('./screens/Student/Dashboard'));
const SchedulerA = lazy(() => import('./screens/Student/SchedulerA'));
const SchedulerB = lazy(() => import('./screens/Student/SchedulerB'));
const SchedulerCExamFlip = lazy(() => import('./screens/Student/SchedulerCExamFlip'));
const ResourceHubList = lazy(() => import('./screens/Student/ResourceHubList'));
const InboxList = lazy(() => import('./screens/Student/InboxList'));
const TicketingList = lazy(() => import('./screens/Student/TicketingList'));
const CurriculumCharts = lazy(() => import('./screens/Student/CurriculumCharts'));
const FormsCalendar = lazy(() => import('./screens/Student/FormsCalendar'));
const AssignmentTrackerList = lazy(() => import('./screens/Student/AssignmentTrackerList'));
const SettingsNotifications = lazy(() => import('./screens/Student/Settings/Notifications'));
const SettingsOfflineQueue = lazy(() => import('./screens/Student/Settings/OfflineQueue'));
const SettingsTheme = lazy(() => import('./screens/Student/Settings/Theme'));

// Professor
const ProfessorDashboard = lazy(() => import('./screens/Professor/Dashboard'));
const ProfessorResources = lazy(() => import('./screens/Professor/ResourcesList'));
const ProfessorUpload = lazy(() => import('./screens/Professor/UploadCenter'));
const ProfessorStudents = lazy(() => import('./screens/Professor/StudentsList'));
const ProfessorMessages = lazy(() => import('./screens/Professor/Messages'));
const ProfessorNotices = lazy(() => import('./screens/Professor/NoticeBoardCRUD'));

// Expert
const ExpertDashboard = lazy(() => import('./screens/Expert/Dashboard'));
const ExpertCourses = lazy(() => import('./screens/Expert/CoursesCRUD'));
const ExpertSpecs = lazy(() => import('./screens/Expert/SpecificationsCRUD'));
const ExpertImport = lazy(() => import('./screens/Expert/ImportExcel'));
const ExpertPending = lazy(() => import('./screens/Expert/PendingResources'));
const ExpertPrereqs = lazy(() => import('./screens/Expert/PrereqManager'));
const ExpertMessaging = lazy(() => import('./screens/Expert/TargetedMessaging'));
const ExpertForms = lazy(() => import('./screens/Expert/FormsManagement'));

// Head
const HeadApprovals = lazy(() => import('./screens/Head/FinalChartApprovalQueue'));
const HeadOversight = lazy(() => import('./screens/Head/ProfessorOversight'));

// Admin
const AdminDashboard = lazy(() => import('./screens/Admin/Dashboard'));
const AdminSemesters = lazy(() => import('./screens/Admin/SemestersManagement'));
const AdminUsers = lazy(() => import('./screens/Admin/UsersManagement'));
const AdminTickets = lazy(() => import('./screens/Admin/TicketsEscalated'));
const AdminBranding = lazy(() => import('./screens/Admin/BrandingLogo'));
const AdminForms = lazy(() => import('./screens/Admin/FormsUniversity'));

// Owner
const OwnerDashboard = lazy(() => import('./screens/Owner/Dashboard'));
const OwnerBulkImport = lazy(() => import('./screens/Owner/BulkImport'));
const OwnerEnvelopes = lazy(() => import('./screens/Owner/ResetPasswordEnvelope'));
const ExpertTickets = lazy(() => import('./screens/Expert/TicketsLane'));
const OwnerAudit = lazy(() => import('./screens/Owner/AuditLogsViewer'));
const OwnerAnalytics = lazy(() => import('./screens/Owner/AnalyticsFull'));
const OwnerSystem = lazy(() => import('./screens/Owner/SystemReadOnlyView'));

/** Route-level lazy loading fallback. */
function ScreenFallback() {
  return (
    <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '60vh' }}>
      <CircularProgress />
    </Box>
  );
}

const theme = createTheme({
  direction: 'rtl',
  palette: {
    primary: { main: '#1976D2' },
    mode: 'light',
  },
  typography: {
    fontFamily: '"Vazirmatn", Tahoma, sans-serif',
  },
});

// TODO-026 fix: emotion cache with the RTL stylis plugin — `direction: 'rtl'`
// in the theme alone does NOT flip generated CSS rules (margins/paddings used
// to stay LTR). Vazirmatn is loaded in fonts.css.
const emotionCache = createCache({
  key: 'mui-rtl',
  stylisPlugins: [prefixer, rtlPlugin],
});

/** Blocks access unless the authenticated user's role is allowed. */
function RoleGuard({ roles }: { roles: string[] }) {
  const { user } = useAuthStore();
  if (!user || !roles.includes(user.role)) {
    return <Navigate to="/login" replace />;
  }
  return <Outlet />;
}

/** Role-aware root redirect. */
function HomeRedirect() {
  const { user } = useAuthStore();
  return <Navigate to={homePathFor(user?.role)} replace />;
}

function App() {
  return (
    <ErrorBoundary>
      <CacheProvider value={emotionCache}>
      <ThemeProvider theme={theme}>
        <CssBaseline />
        <BrowserRouter>
          <Suspense fallback={<ScreenFallback />}>
          <Routes>
            <Route path="/login" element={<Login />} />

            <Route element={<ProtectedRoute />}>
              <Route path="/onboarding" element={<Onboarding />} />

              <Route element={<Layout />}>
                {/* Student */}
                <Route element={<RoleGuard roles={['student']} />}>
                  <Route path="/dashboard" element={<Dashboard />} />
                  <Route path="/scheduler-a" element={<SchedulerA />} />
                  <Route path="/scheduler-b" element={<SchedulerB />} />
                  <Route path="/scheduler-c" element={<SchedulerCExamFlip />} />
                  <Route path="/resources" element={<ResourceHubList />} />
                  <Route path="/inbox" element={<InboxList />} />
                  <Route path="/ticketing" element={<TicketingList />} />
                  <Route path="/curriculum" element={<CurriculumCharts />} />
                  <Route path="/forms-calendar" element={<FormsCalendar />} />
                  <Route path="/assignments" element={<AssignmentTrackerList />} />
                  <Route path="/settings" element={<SettingsNotifications />} />
                  <Route path="/settings/offline-queue" element={<SettingsOfflineQueue />} />
                  <Route path="/settings/theme" element={<SettingsTheme />} />
                </Route>

                {/* Professor */}
                <Route element={<RoleGuard roles={['professor']} />}>
                  <Route path="/professor/dashboard" element={<ProfessorDashboard />} />
                  <Route path="/professor/resources" element={<ProfessorResources />} />
                  <Route path="/professor/upload" element={<ProfessorUpload />} />
                  <Route path="/professor/students" element={<ProfessorStudents />} />
                  <Route path="/professor/messages" element={<ProfessorMessages />} />
                  <Route path="/professor/notices" element={<ProfessorNotices />} />
                </Route>

                {/* Expert */}
                <Route element={<RoleGuard roles={['expert']} />}>
                  <Route path="/expert/dashboard" element={<ExpertDashboard />} />
                  <Route path="/expert/courses" element={<ExpertCourses />} />
                  <Route path="/expert/specifications" element={<ExpertSpecs />} />
                  <Route path="/expert/import" element={<ExpertImport />} />
                  <Route path="/expert/pending-resources" element={<ExpertPending />} />
                  <Route path="/expert/prereqs" element={<ExpertPrereqs />} />
                  <Route path="/expert/messaging" element={<ExpertMessaging />} />
                  <Route path="/expert/forms" element={<ExpertForms />} />
                  <Route path="/expert/tickets" element={<ExpertTickets />} />
                </Route>

                {/* Head */}
                <Route element={<RoleGuard roles={['head_of_dept']} />}>
                  <Route path="/head/approvals" element={<HeadApprovals />} />
                  <Route path="/head/oversight" element={<HeadOversight />} />
                </Route>

                {/* Admin */}
                <Route element={<RoleGuard roles={['admin']} />}>
                  <Route path="/admin/dashboard" element={<AdminDashboard />} />
                  <Route path="/admin/semesters" element={<AdminSemesters />} />
                  <Route path="/admin/users" element={<AdminUsers />} />
                  <Route path="/admin/tickets" element={<AdminTickets />} />
                  <Route path="/admin/branding" element={<AdminBranding />} />
                  <Route path="/admin/forms" element={<AdminForms />} />
                </Route>

                {/* Owner */}
                <Route element={<RoleGuard roles={['owner']} />}>
                  <Route path="/owner/dashboard" element={<OwnerDashboard />} />
                  <Route path="/owner/bulk-import" element={<OwnerBulkImport />} />
                  <Route path="/owner/envelopes" element={<OwnerEnvelopes />} />
                  <Route path="/owner/audit" element={<OwnerAudit />} />
                  <Route path="/owner/analytics" element={<OwnerAnalytics />} />
                  <Route path="/owner/system" element={<OwnerSystem />} />
                </Route>
              </Route>
            </Route>

            <Route path="/" element={<HomeRedirect />} />
            <Route path="*" element={<div>صفحه یافت نشد - Unify V9</div>} />
          </Routes>
          </Suspense>
        </BrowserRouter>
      </ThemeProvider>
      </CacheProvider>
    </ErrorBoundary>
  );
}

export default App;
