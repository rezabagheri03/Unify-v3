import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function TargetedMessaging() {
  const [recipientId, setRecipientId] = useState('');
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const send = async () => {
    try {
      await api.post('/messages/send', { recipient_id: recipientId, subject, body });
      setSnack('پیام خصوصی ارسال شد');
      setRecipientId(''); setSubject(''); setBody('');
    } catch (err: any) {
      setSnack(apiErrorMessage(err, 'ارسال ناموفق (ضد شمارش: پاسخ یکسان برای کاربر نامعتبر)'));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>پیام گروهی / خصوصی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <TextField fullWidth label="شناسه گیرنده (اختیاری — خالی = پیام سیستمی)" value={recipientId} onChange={(e) => setRecipientId(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="موضوع" value={subject} onChange={(e) => setSubject(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="متن" multiline rows={4} value={body} onChange={(e) => setBody(e.target.value)} sx={{ mb: 2 }} />
          <Button variant="contained" onClick={send} disabled={!body}>ارسال</Button>
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={5000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
