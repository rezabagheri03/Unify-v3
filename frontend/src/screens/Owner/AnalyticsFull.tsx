import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function AnalyticsFull() {
  const [data, setData] = useState<any>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const [r, t, u] = await Promise.all([
          api.get('/resources'),
          api.get('/tickets'),
          api.get('/owner/export/users', { responseType: 'text' }).catch(() => ({ data: null })),
        ]);
        if (!active) return;
        const tickets = Array.isArray(t.data?.data) ? t.data.data : Array.isArray(t.data) ? t.data : [];
        const downloads = (r.data?.data || []).reduce((s: number, x: any) => s + (x.download_count || 0), 0);
        setData({
          resources: (r.data?.data || []).length,
          downloads,
          openTickets: tickets.filter((x: any) => ['open', 'in_progress'].includes(x.status)).length,
          totalTickets: tickets.length,
        });
      } catch (err: any) {
        if (active) setError(apiErrorMessage(err));
      }
    })();
    return () => { active = false; };
  }, []);

  if (!data) return <Box><Typography variant="h5">گزارش‌ها</Typography>{error && <Alert severity="error">{error}</Alert>}</Box>;

  return (
    <Box>
      <Typography variant="h5" gutterBottom>گزارش‌ها</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <Typography variant="subtitle1">منابع: {data.resources}</Typography>
          <Typography variant="subtitle1">دانلود کل: {data.downloads}</Typography>
          <Typography variant="subtitle1">تیکت باز: {data.openTickets} / {data.totalTickets}</Typography>
        </CardContent>
      </Card>
    </Box>
  );
}
