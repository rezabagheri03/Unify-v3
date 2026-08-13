import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import StatusBadge from '../../components/StatusBadge';

export default function FinalChartApprovalQueue() {
  const [charts, setCharts] = useState<any[]>([]);
  const [error, setError] = useState('');

  const load = () => {
    api.get('/curriculum', { params: { status: 'pending_approval' } })
      .then((res) => setCharts(Array.isArray(res.data) ? res.data : []))
      .catch((err) => setError(apiErrorMessage(err)));
  };
  useEffect(() => { load(); }, []);

  const act = async (id: string, action: 'approve' | 'reject') => {
    try {
      await api.post(`/curriculum/${id}/${action}`);
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>صف تأیید نمودار درسی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {charts.map((c: any) => (
        <Card key={c.id} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Box>
              <Typography variant="subtitle1">نمودار ورودی {c.entry_year}</Typography>
              <StatusBadge status={c.status} />
            </Box>
            <Box>
              <Button size="small" color="success" variant="contained" onClick={() => act(c.id, 'approve')} sx={{ mr: 1 }}>تأیید</Button>
              <Button size="small" color="error" variant="outlined" onClick={() => act(c.id, 'reject')}>برگشت</Button>
            </Box>
          </CardContent>
        </Card>
      ))}
      {charts.length === 0 && <Typography color="text.secondary">نموداری در انتظار تأیید نیست</Typography>}
    </Box>
  );
}
