import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Grid from '@mui/material/Grid';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../stores/authStore';

export default function ProfessorDashboard() {
  const { user } = useAuthStore();
  const [resources, setResources] = useState(0);
  const [specs, setSpecs] = useState(0);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [r, s] = await Promise.all([
          api.get('/resources', { params: { professor_id: user?.id } }),
          api.get('/specifications'),
        ]);
        if (!active) return;
        setResources((r.data?.data || []).length);
        setSpecs((s.data?.data || []).filter((x: any) => x.professor_id === user?.id).length);
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, [user?.id]);

  const cards = [
    { title: 'منابع من', value: resources },
    { title: 'درس‌های من', value: specs },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>داشبورد استاد</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        {user?.first_name} {user?.last_name} ({user?.id})
      </Typography>
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
