import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import FileCard from '../../components/FileCard';
import { useAuthStore } from '../../stores/authStore';

export default function ResourcesList() {
  const { user } = useAuthStore();
  const [resources, setResources] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/resources', { params: { professor_id: user?.id } })
      .then((res) => setResources(res.data?.data || []))
      .catch((err) => setError(apiErrorMessage(err)));
  }, [user?.id]);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>منابع من</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {resources.map((r: any) => (
        <FileCard key={r.id} id={r.id} title={r.title} author={r.course?.name} average_rating={r.average_rating} rating_count={r.rating_count} download_count={r.download_count} badge_type={r.badge_type} />
      ))}
      {resources.length === 0 && <Typography color="text.secondary">منبعی ثبت نشده</Typography>}
    </Box>
  );
}
