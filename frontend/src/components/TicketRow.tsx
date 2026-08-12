import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import StatusBadge from './StatusBadge';

/** P18 TicketRow — support ticket list row. */
export default function TicketRow({ ticket, onClick }: { ticket: any; onClick?: () => void }) {
  const t = ticket || {};
  return (
    <Card sx={{ mb: 1, cursor: onClick ? 'pointer' : 'default' }} onClick={onClick}>
      <CardContent>
        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
          <Typography variant="subtitle1">{t.subject}</Typography>
          <StatusBadge status={t.status} />
        </Box>
        <Typography variant="body2" color="text.secondary">
          {t.department}
          {t.created_at ? ` • ${new Date(t.created_at).toLocaleString('fa-IR')}` : ''}
          {t.is_escalated ? ' • ارجاع شده' : ''}
        </Typography>
        <Typography variant="body2" sx={{ mt: 0.5 }}>{t.description}</Typography>
      </CardContent>
    </Card>
  );
}
