import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function PrereqManager() {
  const [courses, setCourses] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/specifications')
      .then((res) => {
        const map = new Map<string, any>();
        (res.data?.data || []).forEach((s: any) => s.course && map.set(s.course.code, s.course));
        setCourses([...map.values()]);
      })
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>مدیریت پیش‌نیازها</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        پیش‌نیازها از فایل نمودار درسی (seed_curriculum) بارگذاری می‌شوند و در انتخاب واحد به‌صورت هشدار (نه مانع) نمایش داده می‌شوند.
      </Typography>
      {courses.map((c: any) => (
        <Card key={c.code} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Typography variant="subtitle1">{c.name} ({c.code})</Typography>
            <Chip size="small" label={`${c.credits} واحد`} variant="outlined" />
          </CardContent>
        </Card>
      ))}
      {courses.length === 0 && <Typography color="text.secondary">درسی ثبت نشده</Typography>}
    </Box>
  );
}
