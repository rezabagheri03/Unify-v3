import React from 'react';
import Alert from '@mui/material/Alert';
import { useServerStatus } from '../api/serverStatus';

/**
 * Global banner shown when the backend is unreachable (e.g. the preview
 * environment recycled the API process). Auto-retries every 15s.
 */
export default function ServerBanner() {
  const status = useServerStatus();

  if (status === 'online' || status === 'checking') return null;

  return (
    <Alert severity="error" sx={{ mb: 1 }} icon={false}>
      ⚠️ اتصال به سرور برقرار نیست — در حال تلاش مجدد... (اگر صفحه تازه بارگذاری شده، چند ثانیه صبر کنید)
    </Alert>
  );
}
