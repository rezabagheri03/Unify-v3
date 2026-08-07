import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import Chip from '@mui/material/Chip';

const STATUS_COLOR: Record<string, any> = {
  pending: 'warning', syncing: 'info', synced: 'success', failed: 'error', conflict: 'error',
};

/** P18 OfflineQueueRow — offline sync queue item (F19). */
export default function OfflineQueueRow({ item }: { item: any }) {
  return (
    <Card sx={{ mb: 1 }}>
      <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <Box>
          <Typography variant="subtitle2">{item.type}</Typography>
          <Typography variant="caption" color="text.secondary">
            {item.created_at} {item.last_error ? ` • ${item.last_error}` : ''}
          </Typography>
        </Box>
        <Chip size="small" label={item.status} color={STATUS_COLOR[item.status] || 'default'} />
      </CardContent>
    </Card>
  );
}
