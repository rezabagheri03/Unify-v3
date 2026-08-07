import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Grid from '@mui/material/Grid';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function AdminDashboard() {
  const [stats, setStats] = useState({ pending: 0, escalated: 0, forms: 0 });
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [p, t, f] = await Promise.all([
          api.get('/admin/resources/pending'),
          api.get('/tickets', { params: { escalated: 1 } }),
          api.get('/forms'),
        ]);
        if (!active) return;
        setStats({
          pending: (p.data?.data || []).length,
          escalated: (Array.isArray(t.data?.data) ? t.data.data : Array.isArray(t.data) ? t.data : []).length,
          forms: (Array.isArray(f.data) ? f.data : []).length,
        });
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, []);

  const cards = [
    { title: 'منابع در انتظار', value: stats.pending },
    { title: 'تیکت‌های ارجاعی', value: stats.escalated },
    { title: 'فرم‌ها', value: stats.forms },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>داشبورد مدیریت</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Grid container spacing={2}>
        {cards.map((c) => (
          <Grid item xs={6} md={4} key={c.title}>
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
