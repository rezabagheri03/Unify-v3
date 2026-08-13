import React from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';

export interface CalendarEvent {
  id: string;
  title?: string;
  description?: string;
  start_date_g?: string;
  end_date_g?: string;
  event_type?: string;
  color_code?: string;
}

/** P18 Timeline — horizontal academic calendar cards (F11). */
export default function Timeline({ events }: { events: CalendarEvent[] }) {
  if (!events?.length) return <Typography color="text.secondary">رویدادی ثبت نشده</Typography>;
  return (
    <Box sx={{ display: 'flex', gap: 1.5, overflowX: 'auto', pb: 1 }}>
      {events.map((e) => (
        <Card key={e.id} sx={{ minWidth: 220, borderTop: `4px solid ${e.color_code || '#1976D2'}` }}>
          <CardContent>
            <Typography variant="subtitle2">{e.title}</Typography>
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>
              {e.start_date_g ? new Date(e.start_date_g).toLocaleDateString('fa-IR') : ''}
              {e.end_date_g && e.end_date_g !== e.start_date_g
                ? ` — ${new Date(e.end_date_g).toLocaleDateString('fa-IR')}` : ''}
            </Typography>
            <Typography variant="body2">{e.description}</Typography>
            <Typography variant="caption" color="text.secondary">{e.event_type}</Typography>
          </CardContent>
        </Card>
      ))}
    </Box>
  );
}
