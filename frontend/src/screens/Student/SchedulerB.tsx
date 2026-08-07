import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Paper from '@mui/material/Paper';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

const DAYS = [
  ['sat', 'شنبه'], ['sun', 'یکشنبه'], ['mon', 'دوشنبه'],
  ['tue', 'سه‌شنبه'], ['wed', 'چهارشنبه'], ['thu', 'پنجشنبه'], ['fri', 'جمعه'],
];

export default function SchedulerB() {
  const [enrollments, setEnrollments] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/enrollments')
      .then((res) => setEnrollments(Array.isArray(res.data) ? res.data.filter((e: any) => e.status !== 'archived') : []))
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  const byDay = (day: string) =>
    enrollments.filter((e: any) => e.specification?.day_of_week === day && e.status === 'finalized');

  return (
    <Box>
      <Typography variant="h5" gutterBottom>فاز B — برنامه هفتگی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: 1 }}>
        {DAYS.map(([day, label]) => (
          <Paper key={day} sx={{ p: 1, minHeight: 180, bgcolor: '#fafafa' }}>
            <Typography variant="subtitle2" align="center" sx={{ mb: 1 }}>{label}</Typography>
            {byDay(day).map((e: any) => (
              <Box key={e.id} sx={{ bgcolor: 'primary.main', color: 'white', borderRadius: 1, p: 0.5, mb: 0.5, fontSize: 12 }}>
                {e.specification?.course?.code}
                <br />
                {e.specification?.time_start}–{e.specification?.time_end}
              </Box>
            ))}
            {byDay(day).length === 0 && (
              <Typography variant="caption" color="text.disabled">—</Typography>
            )}
          </Paper>
        ))}
      </Box>
    </Box>
  );
}
