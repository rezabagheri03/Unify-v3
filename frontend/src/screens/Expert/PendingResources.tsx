import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function PendingResources() {
  const [pending, setPending] = useState<any[]>([]);
  const [error, setError] = useState('');

  const load = async () => {
    try {
      const res = await api.get('/admin/resources/pending');
      setPending(Array.isArray(res.data?.data) ? res.data.data : []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => {
    load();
  }, []);

  const act = async (id: string, action: 'approve' | 'reject') => {
    try {
      await api.post(`/admin/resources/${id}/${action}`);
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>منابع در انتظار تأیید</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {pending.map((r: any) => (
        <Card key={r.id} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Box>
              <Typography variant="subtitle1">{r.title}</Typography>
              <Typography variant="body2" color="text.secondary">
                {r.course?.name} • {r.uploader?.first_name} {r.uploader?.last_name} • {r.file_size_bytes} بایت
              </Typography>
            </Box>
            <Box>
              <Button size="small" color="success" variant="contained" onClick={() => act(r.id, 'approve')} sx={{ mr: 1 }}>تأیید</Button>
              <Button size="small" color="error" variant="outlined" onClick={() => act(r.id, 'reject')}>رد</Button>
            </Box>
          </CardContent>
        </Card>
      ))}
      {pending.length === 0 && <Typography color="text.secondary">موردی در انتظار نیست</Typography>}
    </Box>
  );
}
