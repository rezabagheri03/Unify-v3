import React, { useEffect, useState } from 'react';
import { Link as RouterLink, Outlet, useNavigate } from 'react-router-dom';
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Toolbar from '@mui/material/Toolbar';
import Typography from '@mui/material/Typography';
import Drawer from '@mui/material/Drawer';
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemText from '@mui/material/ListItemText';
import Badge from '@mui/material/Badge';
import IconButton from '@mui/material/IconButton';
import Chip from '@mui/material/Chip';
import LogoutIcon from '@mui/icons-material/Logout';
import MenuIcon from '@mui/icons-material/Menu';
import useMediaQuery from '@mui/material/useMediaQuery';
import { useTheme } from '@mui/material/styles';
import { useAuthStore } from '../stores/authStore';
import { useNotificationsPolling } from '../api/polling';
import { useServerStatus } from '../api/serverStatus';
import ServerBanner from './ServerBanner';

interface NavItem {
  to: string;
  label: string;
}

const NAV: Record<string, NavItem[]> = {
  student: [
    { to: '/dashboard', label: 'داشبورد' },
    { to: '/scheduler-a', label: 'انتخاب واحد' },
    { to: '/scheduler-b', label: 'برنامه هفتگی' },
    { to: '/scheduler-c', label: 'برنامه امتحانات' },
    { to: '/resources', label: 'مرکز منابع' },
    { to: '/inbox', label: 'پیام‌ها' },
    { to: '/ticketing', label: 'تیکت‌ها' },
    { to: '/curriculum', label: 'نمودار درسی' },
    { to: '/forms-calendar', label: 'فرم‌ها و تقویم' },
    { to: '/assignments', label: 'تکالیف' },
    { to: '/settings', label: 'تنظیمات' },
  ],
  professor: [
    { to: '/professor/dashboard', label: 'داشبورد' },
    { to: '/professor/resources', label: 'منابع من' },
    { to: '/professor/upload', label: 'آپلود منبع' },
    { to: '/professor/students', label: 'دانشجویان' },
    { to: '/professor/messages', label: 'پیام‌ها' },
    { to: '/professor/notices', label: 'اعلان‌ها' },
  ],
  expert: [
    { to: '/expert/dashboard', label: 'داشبورد' },
    { to: '/expert/courses', label: 'دروس' },
    { to: '/expert/specifications', label: 'مشخصات دروس' },
    { to: '/expert/import', label: 'ایمپورت اکسل' },
    { to: '/expert/pending-resources', label: 'منابع در انتظار' },
    { to: '/expert/prereqs', label: 'پیش‌نیازها' },
    { to: '/expert/messaging', label: 'پیام گروهی' },
    { to: '/expert/forms', label: 'فرم‌ها' },
  ],
  head_of_dept: [
    { to: '/head/approvals', label: 'تأیید نمودارها' },
    { to: '/head/oversight', label: 'نظارت اساتید' },
  ],
  admin: [
    { to: '/admin/dashboard', label: 'داشبورد' },
    { to: '/admin/semesters', label: 'نیم‌سال‌ها' },
    { to: '/admin/users', label: 'کاربران' },
    { to: '/admin/tickets', label: 'تیکت‌های ارجاعی' },
    { to: '/admin/branding', label: 'برندینگ' },
    { to: '/admin/forms', label: 'فرم‌های دانشگاه' },
  ],
  owner: [
    { to: '/owner/dashboard', label: 'داشبورد' },
    { to: '/owner/bulk-import', label: 'ایمپورت انبوه' },
    { to: '/owner/envelopes', label: 'پاکت رمز' },
    { to: '/owner/audit', label: 'لاگ‌های ممیزی' },
    { to: '/owner/analytics', label: 'گزارش‌ها' },
    { to: '/owner/system', label: 'سیستم' },
  ],
};

export default function Layout() {
  const { user, logout } = useAuthStore();
  const navigate = useNavigate();
  const unread = useNotificationsPolling();
  const serverStatus = useServerStatus();

  // TODO-026 fix: the old drawer was a permanent 240px strip on every screen,
  // including phones. Below `md` it becomes a temporary overlay drawer opened
  // from a hamburger button.
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [mobileOpen, setMobileOpen] = React.useState(false);

  const role = (user?.role as keyof typeof NAV) || 'student';
  const items = NAV[role] || NAV.student;

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <Box sx={{ display: 'flex' }}>
      <AppBar position="fixed" sx={{ zIndex: (t) => t.zIndex.drawer + 1 }}>
        <Toolbar>
          {isMobile && (
            <IconButton color="inherit" edge="start" aria-label="منو" onClick={() => setMobileOpen(true)}>
              <MenuIcon />
            </IconButton>
          )}
          <Typography variant="h6" component="div" sx={{ flexGrow: 1 }}>
            Unify
          </Typography>
          <Chip
            size="small"
            color={serverStatus === 'online' ? 'success' : serverStatus === 'checking' ? 'default' : 'error'}
            label={serverStatus === 'online' ? 'سرویس: آنلاین' : serverStatus === 'checking' ? 'در حال بررسی...' : 'سرویس: قطع'}
            sx={{ ml: 1 }}
          />
          <IconButton color="inherit" onClick={handleLogout} aria-label="خروج">
            <LogoutIcon />
          </IconButton>
        </Toolbar>
      </AppBar>
      <Drawer
        variant={isMobile ? 'temporary' : 'permanent'}
        open={isMobile ? mobileOpen : true}
        onClose={() => setMobileOpen(false)}
        ModalProps={{ keepMounted: true }}
        sx={{
          width: 240,
          flexShrink: 0,
          '& .MuiDrawer-paper': { width: 240, boxSizing: 'border-box', mt: 8 },
        }}
      >
        <List onClick={() => isMobile && setMobileOpen(false)}>
          {items.map((item) => (
            <ListItem key={item.to} disablePadding>
              <ListItemButton component={RouterLink} to={item.to}>
                {item.label === 'پیام‌ها' ? (
                  <Badge badgeContent={unread.length} color="primary">
                    <ListItemText primary={item.label} />
                  </Badge>
                ) : (
                  <ListItemText primary={item.label} />
                )}
              </ListItemButton>
            </ListItem>
          ))}
        </List>
      </Drawer>
      <Box component="main" sx={{ flexGrow: 1, p: 3, mt: 8 }}>
        <ServerBanner />
        <Outlet />
      </Box>
    </Box>
  );
}
