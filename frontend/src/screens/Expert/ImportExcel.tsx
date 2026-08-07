import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function ImportExcel() {
  const [file, setFile] = useState<File | null>(null);
  const [kind, setKind] = useState<'users' | 'courses' | 'specifications'>('users');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const importFile = async () => {
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    const url = kind === 'users' ? '/owner/users/bulk-import' : kind === 'courses' ? '/excel/import/courses' : '/excel/import/specifications';
    try {
      const res = await api.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      setSnack(res.data?.message || 'ایمپورت انجام شد');
      setFile(null);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>ایمپورت اکسل (تراکنشی)</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        در صورت وجود خطا در هر ردیف، کل عملیات برگشت می‌خورد و فایل گزارش خطا (ستون قرمز) دانلود می‌شود.
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <Box sx={{ mb: 2, display: 'flex', gap: 2 }}>
            {(['users', 'courses', 'specifications'] as const).map((k) => (
              <Button key={k} variant={kind === k ? 'contained' : 'outlined'} onClick={() => setKind(k)}>
                {k === 'users' ? 'کاربران' : k === 'courses' ? 'دروس' : 'مشخصات دروس'}
              </Button>
            ))}
          </Box>
          <input type="file" accept=".xlsx,.xls" onChange={(e) => setFile(e.target.files?.[0] || null)} />
          <Button variant="contained" sx={{ ml: 2 }} onClick={importFile} disabled={!file}>ایمپورت</Button>
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={5000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
