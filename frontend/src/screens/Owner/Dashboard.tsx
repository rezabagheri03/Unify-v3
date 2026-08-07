import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Grid from '@mui/material/Grid';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function OwnerDashboard() {
  const [stats, setStats] = useState({ resources: 0, tickets: 0, audit: 0, semesters: 0 });
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [r, t, a, s] = await Promise.all([
          api.get('/resources'),
          api.get('/tickets'),
          api.get('/owner/audit-logs'),
          api.get('/semesters/past'),
        ]);
        if (!active) return;
        const tickets = Array.isArray(t.data?.data) ? t.data.data : Array.isArray(t.data) ? t.data : [];
        const audits = Array.isArray(a.data?.data) ? a.data.data : Array.isArray(a.data) ? a.data : [];
        setStats({
          resources: (r.data?.data || []).length,
          tickets: tickets.length,
          audit: audits.length,
          semesters: (Array.isArray(s.data) ? s.data : []).length + 1,
        });
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, []);

  const cards = [
    { title: 'منابع', value: stats.resources },
    { title: 'تیکت‌ها', value: stats.tickets },
    { title: 'لاگ‌های ممیزی', value: stats.audit },
    { title: 'نیم‌سال‌ها', value: stats.semesters },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>داشبورد مالک سیستم</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Grid container spacing={2}>
        {cards.map((c) => (
          <Grid item xs={6} md={3} key={c.title}>
            <Card>
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
