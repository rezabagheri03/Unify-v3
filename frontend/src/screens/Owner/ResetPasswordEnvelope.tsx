import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import MenuItem from '@mui/material/MenuItem';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function ResetPasswordEnvelope() {
  const [userId, setUserId] = useState('');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');
  const [scope, setScope] = useState('students');
  const [busyZip, setBusyZip] = useState(false);

  // V2-07: the bulk ZIP endpoint existed server-side with no UI caller —
  // onboarding day would have meant 600 single-user clicks. Wired for real.
  const bulkZip = async () => {
    if (busyZip) return;
    setBusyZip(true);
    setError('');
    try {
      const res = await api.post(
        '/owner/generate-envelopes',
        { scope },
        { responseType: 'blob', timeout: 620000 } // V2-01: server allows 600s
      );
      const url = URL.createObjectURL(res.data);
      const a = document.createElement('a');
      a.href = url;
      a.download = `envelopes-${scope}.zip`;
      a.click();
      URL.revokeObjectURL(url);
      setSnack('ZIP پاکت‌ها دانلود شد');
    } catch (err: any) {
      setError(apiErrorMessage(err));
    } finally {
      setBusyZip(false);
    }
  };

  const envelope = async () => {
    if (!userId) return;
    try {
      const res = await api.post(`/owner/users/${userId}/reset-password`, {}, { responseType: 'blob' });
      const url = URL.createObjectURL(res.data);
      const a = document.createElement('a');
      a.href = url;
      a.download = `envelope-${userId}.pdf`;
      a.click();
      URL.revokeObjectURL(url);
      setSnack('پاکت رمز تولید شد');
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>پاکت رمز (IT Handout)</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <TextField fullWidth label="شناسه کاربر" value={userId} onChange={(e) => setUserId(e.target.value)} sx={{ mb: 2 }} />
          <Button variant="contained" onClick={envelope} disabled={!userId}>تولید پاکت (PDF)</Button>
        </CardContent>
      </Card>
      <Card sx={{ mt: 3 }}>
        <CardContent>
          <Typography variant="subtitle1" gutterBottom>تولید انبوه پاکت (ZIP)</Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            برای همه کاربرانی که هنوز رمز موقت استفاده نکرده‌اند. فایل ZIP حاوی یک پاکت PDF به‌علاوه QR برای هر کاربر است.
          </Typography>
          <TextField
            select
            label="مخاطبان"
            value={scope}
            onChange={(e) => setScope(e.target.value)}
            sx={{ mb: 2, minWidth: 220 }}
          >
            <MenuItem value="students">دانشجویان</MenuItem>
            <MenuItem value="staff">کارکنان</MenuItem>
            <MenuItem value="all">همه</MenuItem>
          </TextField>
          <Box>
            <Button variant="contained" color="secondary" onClick={bulkZip} disabled={busyZip}>
              {busyZip ? 'در حال تولید ZIP (چند دقیقه طول می‌کشد)…' : 'دانلود ZIP پاکت‌ها'}
            </Button>
          </Box>
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
