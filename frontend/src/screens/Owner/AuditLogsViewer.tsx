import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function AuditLogsViewer() {
  const [logs, setLogs] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/owner/audit-logs')
      .then((res) => setLogs(Array.isArray(res.data?.data) ? res.data.data : Array.isArray(res.data) ? res.data : []))
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>لاگ‌های ممیزی</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        جزئیات به‌صورت رمزنگاری‌شده ذخیره می‌شوند (AuditLog + Crypt).
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {logs.map((l: any) => (
        <Card key={l.id} sx={{ mb: 1 }}>
          <CardContent>
            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
              <Typography variant="subtitle2">
                {l.action} — {l.resource_type}
              </Typography>
              <Chip size="small" label={new Date(l.timestamp).toLocaleString('fa-IR')} />
            </Box>
            <Typography variant="caption" color="text.secondary">
              کاربر: {l.user?.id || '—'} • IP: {l.ip_address}
            </Typography>
          </CardContent>
        </Card>
      ))}
      {logs.length === 0 && <Typography color="text.secondary">لاگی ثبت نشده</Typography>}
    </Box>
  );
}
