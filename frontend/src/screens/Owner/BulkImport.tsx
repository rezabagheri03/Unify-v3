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

export default function BulkImport() {
  const [file, setFile] = useState<File | null>(null);
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const [progress, setProgress] = useState(0);
  const [busy, setBusy] = useState(false);

  const upload = async () => {
    if (!file || busy) return;
    const fd = new FormData();
    fd.append('file', file);
    setBusy(true); setProgress(0); setError('');
    try {
      const res = await api.post('/owner/users/bulk-import', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
        // V2-01: 600-row import = ~600 reduced-profile hashes server-side
        // (set_time_limit 120s). The 30s default aborted mid-processing.
        timeout: 180000,
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
      <Typography variant="h5" gutterBottom>ایمپورت انبوه دانشجویان</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        قالب اکسل: شماره دانشجویی، نام، نام خانوادگی، نقش، دانشکده، وضعیت — حداکثر ۶۰۰ ردیف در هر فایل.
        پس از ایمپورت، از صفحه «پاکت رمز» برای تولید ZIP پاکت‌ها استفاده کنید.
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <input type="file" accept=".xlsx,.xls" onChange={(e) => setFile(e.target.files?.[0] || null)} disabled={busy} />
          <Button variant="contained" sx={{ ml: 2 }} onClick={upload} disabled={!file || busy}>ایمپورت</Button>
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
