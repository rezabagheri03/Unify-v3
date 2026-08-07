import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function BulkImport() {
  const [file, setFile] = useState<File | null>(null);
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const upload = async () => {
    if (!file) return;
    const fd = new FormData();
    fd.append('file', file);
    try {
      const res = await api.post('/owner/users/bulk-import', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      setSnack(res.data?.message || 'ایمپورت انجام شد');
      setFile(null);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>ایمپورت انبوه دانشجویان</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        قالب اکسل: شماره دانشجویی، نام، نام خانوادگی، نقش، دانشکده، وضعیت — حداکثر ۲۰۰۰ ردیف.
        پس از ایمپورت، از صفحه «پاکت رمز» برای تولید ZIP پاکت‌ها استفاده کنید.
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <input type="file" accept=".xlsx,.xls" onChange={(e) => setFile(e.target.files?.[0] || null)} />
          <Button variant="contained" sx={{ ml: 2 }} onClick={upload} disabled={!file}>ایمپورت</Button>
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={5000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
