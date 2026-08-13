import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import TicketRow from '../../components/TicketRow';

export default function TicketsEscalated() {
  const [tickets, setTickets] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/tickets', { params: { escalated: 1 } })
      .then((res) => {
        const list = Array.isArray(res.data?.data) ? res.data.data : Array.isArray(res.data) ? res.data : [];
        setTickets(list);
      })
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>تیکت‌های ارجاع‌شده</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {tickets.map((t: any) => <TicketRow key={t.id} ticket={t} />)}
      {tickets.length === 0 && <Typography color="text.secondary">تیکت ارجاع‌شده‌ای نیست</Typography>}
    </Box>
  );
}
