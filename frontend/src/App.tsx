import React from 'react';
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import { CssBaseline } from '@mui/material';
import { useAuthStore } from './stores/authStore';
import { ErrorBoundary } from './components/ErrorBoundary';
import { ProtectedRoute } from './components/ProtectedRoute';
import Layout from './components/Layout';
import { homePathFor } from './utils/navigation';

// Auth
import Login from './screens/Auth/Login';
import Onboarding from './screens/Auth/Onboarding';

// Student
import Dashboard from './screens/Student/Dashboard';
import SchedulerA from './screens/Student/SchedulerA';
import SchedulerB from './screens/Student/SchedulerB';
import SchedulerCExamFlip from './screens/Student/SchedulerCExamFlip';
import ResourceHubList from './screens/Student/ResourceHubList';
import InboxList from './screens/Student/InboxList';
import TicketingList from './screens/Student/TicketingList';
import CurriculumCharts from './screens/Student/CurriculumCharts';
import FormsCalendar from './screens/Student/FormsCalendar';
import AssignmentTrackerList from './screens/Student/AssignmentTrackerList';
import SettingsNotifications from './screens/Student/Settings/Notifications';
import SettingsOfflineQueue from './screens/Student/Settings/OfflineQueue';
import SettingsTheme from './screens/Student/Settings/Theme';

// Professor
import ProfessorDashboard from './screens/Professor/Dashboard';
import ProfessorResources from './screens/Professor/ResourcesList';
import ProfessorUpload from './screens/Professor/UploadCenter';
import ProfessorStudents from './screens/Professor/StudentsList';
import ProfessorMessages from './screens/Professor/Messages';
import ProfessorNotices from './screens/Professor/NoticeBoardCRUD';

// Expert
import ExpertDashboard from './screens/Expert/Dashboard';
import ExpertCourses from './screens/Expert/CoursesCRUD';
import ExpertSpecs from './screens/Expert/SpecificationsCRUD';
import ExpertImport from './screens/Expert/ImportExcel';
import ExpertPending from './screens/Expert/PendingResources';
import ExpertPrereqs from './screens/Expert/PrereqManager';
import ExpertMessaging from './screens/Expert/TargetedMessaging';
import ExpertForms from './screens/Expert/FormsManagement';

// Head
import HeadApprovals from './screens/Head/FinalChartApprovalQueue';
import HeadOversight from './screens/Head/ProfessorOversight';

// Admin
import AdminDashboard from './screens/Admin/Dashboard';
import AdminSemesters from './screens/Admin/SemestersManagement';
import AdminUsers from './screens/Admin/UsersManagement';
import AdminTickets from './screens/Admin/TicketsEscalated';
import AdminBranding from './screens/Admin/BrandingLogo';
import AdminForms from './screens/Admin/FormsUniversity';

// Owner
import OwnerDashboard from './screens/Owner/Dashboard';
import OwnerBulkImport from './screens/Owner/BulkImport';
import OwnerEnvelopes from './screens/Owner/ResetPasswordEnvelope';
import OwnerAudit from './screens/Owner/AuditLogsViewer';
import OwnerAnalytics from './screens/Owner/AnalyticsFull';
import OwnerSystem from './screens/Owner/SystemReadOnlyView';

const theme = createTheme({
  direction: 'rtl',
  palette: {
    primary: { main: '#1976D2' },
    mode: 'light',
  },
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
      <ThemeProvider theme={theme}>
        <CssBaseline />
        <BrowserRouter>
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
        </BrowserRouter>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
