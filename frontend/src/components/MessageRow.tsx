import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Chip from '@mui/material/Chip';
import Box from '@mui/material/Box';

/** P18 MessageRow — single inbox row with read/edited/deleted states. */
export default function MessageRow({ message, onClick }: { message: any; onClick?: () => void }) {
  const m = message || {};
  return (
    <Card sx={{ mb: 1, cursor: onClick ? 'pointer' : 'default' }} onClick={onClick}>
      <CardContent>
        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
          <Typography variant="subtitle1">
            {m.subject || '(بدون موضوع)'}
            {m.is_edited && <Chip size="small" label="ویرایش شده" sx={{ ml: 1 }} />}
            {m.is_deleted && <Chip size="small" label="حذف شده" color="error" sx={{ ml: 1 }} />}
          </Typography>
          <Chip size="small" label={m.priority || 'normal'} variant="outlined" />
        </Box>
        <Typography variant="body2" color="text.secondary">
          {m.sender?.first_name} {m.sender?.last_name}
          {m.sent_at ? ` • ${new Date(m.sent_at).toLocaleString('fa-IR')}` : ''}
        </Typography>
        <Typography variant="body2" sx={{ mt: 0.5 }}>{m.body}</Typography>
      </CardContent>
    </Card>
  );
}
