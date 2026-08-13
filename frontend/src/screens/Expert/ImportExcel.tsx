import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import LinearProgress from '@mui/material/LinearProgress';
import api, { apiErrorMessage } from '../../api/client';

export default function ImportExcel() {
  const [file, setFile] = useState<File | null>(null);
  // Users import is owner-only (ROLES: owner manages accounts) — this screen
  // covers the expert/admin curriculum imports. The old 'users' tab pointed at
  // /excel/import/* which never existed as a route (always 404).
  const [kind, setKind] = useState<'courses' | 'specifications'>('courses');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const [progress, setProgress] = useState(0);
  const [busy, setBusy] = useState(false);

  const importFile = async () => {
    if (!file || busy) return;
    const fd = new FormData();
    fd.append('file', file);
    const url = kind === 'courses' ? '/admin/import/courses' : '/admin/import/specifications';
    setBusy(true); setProgress(0); setError('');
    try {
      const res = await api.post(url, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
        // Upload leg only; the processing leg is server-side (two-pass, D-001).
        timeout: 120000, // V2-01
        onUploadProgress: (e) => setProgress(e.total ? Math.round((e.loaded / e.total) * 100) : 0),
      });
      setSnack(res.data?.message || 'ایمپورت انجام شد');
      setFile(null);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    } finally {
      setBusy(false);
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
            {(['courses', 'specifications'] as const).map((k) => (
              <Button key={k} variant={kind === k ? 'contained' : 'outlined'} onClick={() => setKind(k)}>
                {k === 'courses' ? 'دروس' : 'مشخصات دروس'}
              </Button>
            ))}
          </Box>
          <input type="file" accept=".xlsx,.xls" onChange={(e) => setFile(e.target.files?.[0] || null)} disabled={busy} />
          <Button variant="contained" sx={{ ml: 2 }} onClick={importFile} disabled={!file || busy}>ایمپورت</Button>
          {busy && (
            <Box sx={{ mt: 2 }}>
              <LinearProgress variant={progress > 0 && progress < 100 ? 'determinate' : 'indeterminate'} value={progress} />
              <Typography variant="caption" color="text.secondary">
                {progress < 100 ? `در حال بارگذاری… ${progress}٪` : 'در حال پردازش روی سرور…'}
              </Typography>
            </Box>
          )}
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={5000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
