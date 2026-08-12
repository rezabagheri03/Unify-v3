import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function ResetPasswordEnvelope() {
  const [userId, setUserId] = useState('');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

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
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
