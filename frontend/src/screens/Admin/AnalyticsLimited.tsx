import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function AnalyticsLimited() {
  const [stats, setStats] = useState({ resources: 0, tickets: 0 });
  const [error, setError] = useState('');

  useEffect(() => {
    Promise.all([api.get('/resources'), api.get('/tickets')])
      .then(([r, t]) => {
        const tickets = Array.isArray(t.data?.data) ? t.data.data : Array.isArray(t.data) ? t.data : [];
        setStats({ resources: (r.data?.data || []).length, tickets: tickets.length });
      })
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>گزارش‌های محدود</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <Typography variant="subtitle1">منابع: {stats.resources}</Typography>
          <Typography variant="subtitle1">تیکت‌ها: {stats.tickets}</Typography>
        </CardContent>
      </Card>
    </Box>
  );
}
