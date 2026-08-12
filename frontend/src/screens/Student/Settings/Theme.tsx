import React from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';

export default function SettingsTheme() {
  return (
    <Box>
      <Typography variant="h5" gutterBottom>ظاهر</Typography>
      <Card>
        <CardContent>
          <Typography variant="body2">
            تم پیش‌فرض: Unify Blue (#1976D2) — حالت تیره و تم‌های سفارشی در این نسخه فعال خواهد شد.
          </Typography>
        </CardContent>
      </Card>
    </Box>
  );
}
