import React, { useEffect, useState } from 'react';
import { Link as RouterLink } from 'react-router-dom';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Grid from '@mui/material/Grid';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../stores/authStore';

export default function Dashboard() {
  const { user } = useAuthStore();
  const [semester, setSemester] = useState<any>(null);
  const [tempCount, setTempCount] = useState(0);
  const [finalizedCount, setFinalizedCount] = useState(0);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [sem, enr] = await Promise.all([
          api.get('/semesters/current'),
          api.get('/enrollments'),
        ]);
        if (!active) return;
        setSemester(sem.data);
        const list = Array.isArray(enr.data) ? enr.data : [];
        setTempCount(list.filter((e: any) => e.status === 'temporary').length);
        setFinalizedCount(list.filter((e: any) => e.status === 'finalized').length);
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, []);

  const cards = [
    { title: 'لیست موقت', value: tempCount, to: '/scheduler-a' },
    { title: 'واحدهای نهایی', value: finalizedCount, to: '/scheduler-b' },
    { title: 'مرکز منابع', value: '—', to: '/resources' },
    { title: 'پیام‌ها', value: '—', to: '/inbox' },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        داشبورد دانشجو
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        {user?.first_name} {user?.last_name} ({user?.id})
      </Typography>

      {user?.academic_status_declared && (
        <Alert severity="warning" sx={{ mb: 2 }}>
          وضعیت تحصیلی خوداظهاری: {user.academic_status_declared} — مسئولیت صحت اطلاعات با شماست (سیستم افتخار)
        </Alert>
      )}
      {semester && (
        <Alert severity="info" sx={{ mb: 2 }} icon={false}>
          نیم‌سال: {semester.name} — وضعیت: {semester.global_state}
          {semester.grace_active ? ' (مهلت ۲۴ ساعته فعال)' : ''}
        </Alert>
      )}
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

      <Grid container spacing={2}>
        {cards.map((c) => (
          <Grid item xs={6} md={3} key={c.title}>
            <Card component={RouterLink} to={c.to} sx={{ textDecoration: 'none', display: 'block' }}>
              <CardContent>
                <Typography color="text.secondary" variant="body2">{c.title}</Typography>
                <Typography variant="h4">{c.value}</Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>
    </Box>
  );
}
