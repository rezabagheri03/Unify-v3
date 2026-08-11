import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Grid from '@mui/material/Grid';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import type { OwnerStats } from '../../api/types';

/**
 * Owner analytics (PERF-15 fix): previously downloaded the ENTIRE users XLSX
 * as text per view plus two paginated lists; now served by one aggregate
 * endpoint + the daily storage stat row.
 */
export default function AnalyticsFull() {
  const [data, setData] = useState<OwnerStats | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [s, st] = await Promise.all([
          api.get('/owner/stats'),
          api.get('/monitoring/storage'),
        ]);
        if (!active) return;
        setData({ ...(s.data as OwnerStats), storage: st.data as OwnerStats['storage'] });
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, []);

  if (!data) return <Box><Typography variant="h5">گزارش‌ها</Typography>{error && <Alert severity="error">{error}</Alert>}</Box>;

  const roles: Record<string, string> = {
    student: 'دانشجو', professor: 'استاد', expert: 'کارشناس',
    admin: 'مدیر', head_of_dept: 'رئیس گروه', owner: 'مالک',
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>گزارش‌ها</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Grid container spacing={2}>
        <Grid item xs={12} md={6}>
          <Card><CardContent>
            <Typography variant="h6" gutterBottom>کاربران</Typography>
            <Typography>مجموع: {data.users_total}</Typography>
            {Object.entries(data.users_by_role || {}).map(([role, count]) => (
              <Typography key={role} variant="body2">{roles[role] || role}: {String(count)}</Typography>
            ))}
            <Typography variant="body2">بن‌شده: {data.users_banned} — در انتظار تغییر رمز: {data.users_pending_password}</Typography>
          </CardContent></Card>
        </Grid>
        <Grid item xs={12} md={6}>
          <Card><CardContent>
            <Typography variant="h6" gutterBottom>منابع و تیکت‌ها</Typography>
            <Typography>منابع تأییدشده: {data.resources_approved}</Typography>
            <Typography>در انتظار تأیید: {data.resources_pending}</Typography>
            <Typography>تیکت باز: {data.tickets_open} — escalate‌شده: {data.tickets_escalated}</Typography>
          </CardContent></Card>
        </Grid>
        <Grid item xs={12} md={6}>
          <Card><CardContent>
            <Typography variant="h6" gutterBottom>فضای ذخیره‌سازی</Typography>
            <Typography>
              {data.storage?.used_gb ?? Math.round((data.storage_used_bytes || 0) / 1024 / 1024 / 1024 * 100) / 100}
              {' '}از {data.storage?.limit_gb ?? 50} گیگ ({data.storage?.percentage ?? '—'}٪)
            </Typography>
            <Typography variant="body2">نیم‌سال جاری: {data.current_semester ?? '—'}</Typography>
          </CardContent></Card>
        </Grid>
      </Grid>
    </Box>
  );
}
