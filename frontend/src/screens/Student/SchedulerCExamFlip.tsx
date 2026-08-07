import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function SchedulerCExamFlip() {
  const [enrollments, setEnrollments] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/enrollments')
      .then((res) => setEnrollments(Array.isArray(res.data) ? res.data.filter((e: any) => e.status === 'finalized') : []))
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  const withExam = enrollments.filter((e: any) => e.specification?.exam_date_final_g);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>برنامه امتحانات</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {withExam.map((e: any) => (
        <Card key={e.id} sx={{ mb: 1 }}>
          <CardContent>
            <Typography variant="subtitle1">{e.specification.course?.name}</Typography>
            <Typography variant="body2" color="text.secondary">
              امتحان: {e.specification.shamsi_final || new Date(e.specification.exam_date_final_g).toLocaleDateString('fa-IR')}
              {e.specification.exam_date_midterm_g && (
                <> • میان‌ترم: {e.specification.shamsi_midterm || new Date(e.specification.exam_date_midterm_g).toLocaleDateString('fa-IR')}</>
              )}
            </Typography>
          </CardContent>
        </Card>
      ))}
      {withExam.length === 0 && <Typography color="text.secondary">امتحانی ثبت نشده</Typography>}
    </Box>
  );
}
