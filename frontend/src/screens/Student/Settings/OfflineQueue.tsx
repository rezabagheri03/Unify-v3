import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Chip from '@mui/material/Chip';
import Button from '@mui/material/Button';
import { SyncQueue } from '../../../db/idb';
import { syncOfflineQueue } from '../../../utils/offlineSync';

const STATUS_COLOR: Record<string, any> = {
  pending: 'warning', syncing: 'info', synced: 'success', failed: 'error', conflict: 'error',
};

export default function SettingsOfflineQueue() {
  const [items, setItems] = useState<any[]>([]);

  const load = async () => {
    setItems(await SyncQueue.getAll());
  };

  useEffect(() => {
    load();
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>صف آفلاین</Typography>
      <Button variant="contained" sx={{ mb: 2 }} onClick={async () => { await syncOfflineQueue(); load(); }}>
        همگام‌سازی دستی
      </Button>
      {items.map((i: any) => (
        <Card key={i.id} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', justifyContent: 'space-between' }}>
            <Box>
              <Typography variant="subtitle2">{i.type}</Typography>
              <Typography variant="caption" color="text.secondary">{i.created_at}</Typography>
            </Box>
            <Chip size="small" label={i.status} color={STATUS_COLOR[i.status] || 'default'} />
          </CardContent>
        </Card>
      ))}
      {items.length === 0 && <Typography color="text.secondary">صف خالی است</Typography>}
    </Box>
  );
}
