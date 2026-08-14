import React from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';

/**
 * Placeholder screen. TODO-046 evidence fix: the previous version fired
 * GET /enrollments AND GET /specifications on every mount whose responses
 * were both discarded — two wasted requests per visit. This screen is
 * intentionally static until the professor-roster endpoint exists.
 */
export default function StudentsList() {
  const students: any[] = [];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>دانشجویان</Typography>
      <Card>
        <CardContent>
          <Typography variant="body2" color="text.secondary">
            فهرست دانشجویان دوره‌های شما (در نسخه کامل از طریق بخش مدیریت کاربران در دسترس است).
          </Typography>
        </CardContent>
      </Card>
      {students.length === 0 && <Typography color="text.secondary">هیچ دانشجویی ثبت نشده</Typography>}
    </Box>
  );
}
