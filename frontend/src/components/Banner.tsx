import React from 'react';
import Alert from '@mui/material/Alert';

export type BannerTone = 'critical' | 'warning' | 'info' | 'success' | 'offline' | 'intranet' | 'honor';

const TONES: Record<BannerTone, { severity: 'error' | 'warning' | 'info' | 'success'; defaultText: string }> = {
  critical: { severity: 'error', defaultText: 'خطای بحرانی' },
  warning: { severity: 'warning', defaultText: 'هشدار' },
  info: { severity: 'info', defaultText: 'اطلاع‌رسانی' },
  success: { severity: 'success', defaultText: 'موفق' },
  offline: { severity: 'error', defaultText: 'آفلاین — عملیات در صف آفلاین ذخیره می‌شود' },
  intranet: { severity: 'warning', defaultText: 'حالت اینترانت — بروزرسانی هر ۳۰ ثانیه' },
  honor: { severity: 'warning', defaultText: 'خوداظهاری: وضعیت تحصیلی — مسئولیت با شماست' },
};

/** P18 Banner — unified banner component for all system states (F02/F15). */
export default function Banner({ tone = 'info', children }: { tone?: BannerTone; children?: React.ReactNode }) {
  const t = TONES[tone];
  return (
    <Alert severity={t.severity} sx={{ mb: 1 }}>
      {children ?? t.defaultText}
    </Alert>
  );
}
