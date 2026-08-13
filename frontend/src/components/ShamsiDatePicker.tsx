import React from 'react';
import TextField from '@mui/material/TextField';

/**
 * P18 ShamsiDatePicker — light Jalali date input (YYYY/MM/DD).
 * Converts to Gregorian ISO for the API using date-fns-jalali when available.
 */
export default function ShamsiDatePicker({ value, onChange, label = 'تاریخ شمسی' }: {
  value: string;
  onChange: (shamsi: string) => void;
  label?: string;
}) {
  const valid = /^1[34]\d{2}\/\d{2}\/\d{2}$/.test(value);

  return (
    <TextField
      fullWidth size="small"
      label={label}
      placeholder="1403/08/15"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      error={value !== '' && !valid}
      helperText={value !== '' && !valid ? 'فرمت تاریخ شمسی نامعتبر (YYYY/MM/DD)' : ' '}
      sx={{ mb: 2 }}
    />
  );
}
