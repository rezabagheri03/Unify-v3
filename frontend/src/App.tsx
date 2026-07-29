import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import { CssBaseline } from '@mui/material';
import { useAuthStore } from './stores/authStore';
import { ErrorBoundary } from './components/ErrorBoundary';

// Auth
import Login from './screens/Auth/Login';
import Onboarding from './screens/Auth/Onboarding';

// Student
import StudentDashboard from './screens/Student/Dashboard';
import SchedulerA from './screens/Student/SchedulerA';
import ResourceHubList from './screens/Student/ResourceHubList';

// Shared
import { ProtectedRoute } from './components/ProtectedRoute';

const theme = createTheme({
  direction: 'rtl',
  palette: {
    primary: { main: '#1976D2' },
  },
});

function App() {
  const { isAuthenticated, user } = useAuthStore();

  return (
    <ErrorBoundary>
      <ThemeProvider theme={theme}>
        <CssBaseline />
        <BrowserRouter>
          <Routes>
            {/* Public */}
            <Route path="/login" element={<Login />} />
            
            {/* Protected */}
            <Route element={<ProtectedRoute />}>
              <Route path="/onboarding" element={<Onboarding />} />
              
              {/* Student Routes */}
              <Route path="/dashboard" element={<StudentDashboard />} />
              <Route path="/scheduler-a" element={<SchedulerA />} />
              <Route path="/resources" element={<ResourceHubList />} />
              
              {/* Default redirect */}
              <Route path="/" element={<Navigate to="/dashboard" />} />
            </Route>

            <Route path="*" element={<div>صفحه یافت نشد - Unify V9</div>} />
          </Routes>
        </BrowserRouter>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;