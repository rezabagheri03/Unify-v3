import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Grid from '@mui/material/Grid';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function ExpertDashboard() {
  const [stats, setStats] = useState({ courses: 0, specs: 0, pending: 0 });
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [s, p] = await Promise.all([
          api.get('/specifications'),
          api.get('/admin/resources/pending'),
        ]);
        if (!active) return;
        setStats({
          courses: new Set((s.data?.data || []).map((x: any) => x.course_id)).size,
          specs: (s.data?.data || []).length,
          pending: (p.data?.data || []).length,
        });
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, []);

  const cards = [
    { title: 'دروس', value: stats.courses },
    { title: 'مشخصات دروس', value: stats.specs },
    { title: 'منابع در انتظار', value: stats.pending },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>داشبورد کارشناس</Typography>
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
