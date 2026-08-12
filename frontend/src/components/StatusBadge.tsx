import React from 'react';
import Chip from '@mui/material/Chip';

const MAP: Record<string, { label: string; color: 'default' | 'primary' | 'success' | 'warning' | 'error' | 'info' }> = {
  // tickets
  open: { label: 'باز', color: 'default' },
  in_progress: { label: 'در حال بررسی', color: 'primary' },
  answered: { label: 'پاسخ داده شد', color: 'success' },
  closed: { label: 'بسته', color: 'default' },
  // resources
  pending: { label: 'در انتظار', color: 'warning' },
  approved: { label: 'تأیید شده', color: 'success' },
  rejected: { label: 'رد شده', color: 'error' },
  // assignments
  submitted: { label: 'تحویل داده شد', color: 'info' },
  graded: { label: 'تصحیح شد', color: 'success' },
  late: { label: 'دیرکرد', color: 'error' },
  missed: { label: 'جاافتاده', color: 'error' },
  // curriculum
  draft: { label: 'پیش‌نویس', color: 'default' },
  pending_approval: { label: 'در انتظار تأیید', color: 'warning' },
};

/** P18 StatusBadge — uniform Persian status chip across entities. */
export default function StatusBadge({ status }: { status: string }) {
  const m = MAP[status] || { label: status, color: 'default' as const };
  return <Chip size="small" label={m.label} color={m.color} />;
}
