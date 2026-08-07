import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function SystemReadOnlyView() {
  const [info, setInfo] = useState<any>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/health')
      .then((res) => setInfo(res.data))
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>وضعیت سیستم</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {info && (
        <Card>
          <CardContent>
            <Typography variant="subtitle1">وضعیت: {info.status}</Typography>
            <Typography variant="subtitle1">نسخه: {info.version}</Typography>
            <Typography variant="subtitle1">حالت: {info.mode}</Typography>
            <Typography variant="body2" color="text.secondary">{info.timestamp}</Typography>
          </CardContent>
        </Card>
      )}
    </Box>
  );
}
