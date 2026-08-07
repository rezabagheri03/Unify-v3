import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import CourseCard from '../../components/CourseCard';

export default function SpecificationsCRUD() {
  const [specs, setSpecs] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/specifications')
      .then((res) => setSpecs(res.data?.data || []))
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>مشخصات دروس ({specs.length})</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {specs.map((s: any) => (
        <CourseCard key={s.id} spec={s} />
      ))}
      {specs.length === 0 && <Typography color="text.secondary">مشخصه‌ای ثبت نشده</Typography>}
    </Box>
  );
}
