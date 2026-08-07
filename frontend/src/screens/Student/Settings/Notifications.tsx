import React from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Link from '@mui/material/Link';
import { Link as RouterLink } from 'react-router-dom';

export default function SettingsNotifications() {
  return (
    <Box>
      <Typography variant="h5" gutterBottom>تنظیمات</Typography>
      <Card sx={{ mb: 1 }}>
        <CardContent>
          <Typography variant="subtitle1">اعلان‌ها</Typography>
          <Typography variant="body2" color="text.secondary">
            اعلان‌ها هر ۳۰ ثانیه (پس‌زمینه ۱۲۰ ثانیه) بررسی می‌شوند. در حالت اینترانت، اعلان‌های فوری از طریق پوش‌اندروید (Pushe) ارسال می‌شوند.
          </Typography>
        </CardContent>
      </Card>
      <Card sx={{ mb: 1 }}>
        <CardContent>
          <Link component={RouterLink} to="/settings/offline-queue">صف آفلاین</Link>
        </CardContent>
      </Card>
      <Card sx={{ mb: 1 }}>
        <CardContent>
          <Link component={RouterLink} to="/settings/theme">ظاهر</Link>
        </CardContent>
      </Card>
    </Box>
  );
}
