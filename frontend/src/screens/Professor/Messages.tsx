import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import TextField from '@mui/material/TextField';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';
import MessageRow from '../../components/MessageRow';

export default function ProfessorMessages() {
  const [messages, setMessages] = useState<any[]>([]);
  const [specs, setSpecs] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [specId, setSpecId] = useState('');
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [error, setError] = useState('');
  const [snack, setSnack] = useState('');

  const load = async () => {
    try {
      const res = await api.get('/messages');
      setMessages(res.data?.data || []);
      const s = await api.get('/specifications');
      setSpecs(s.data?.data || []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => { load(); }, []);

  const broadcast = async () => {
    try {
      await api.post('/messages/send', { specification_id: specId, subject, body });
      setSnack('پیام کلاسی ارسال شد');
      setOpen(false); setSubject(''); setBody(''); setSpecId('');
      load();
    } catch (err: any) {
      setSnack(apiErrorMessage(err, 'خطا (محدودیت پخش: هر ۱۰ دقیقه یک پیام)'));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>پیام‌ها و اطلاع‌رسانی کلاسی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>پیام به کلاس (پخش)</Button>
      {messages.map((m: any) => <MessageRow key={m.id} message={m} />)}
      {messages.length === 0 && <Typography color="text.secondary">پیامی نیست</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>پخش پیام کلاسی (۱ بار / ۱۰ دقیقه)</DialogTitle>
        <DialogContent>
          <TextField select fullWidth label="کلاس (درس)" value={specId} onChange={(e) => setSpecId(e.target.value)} sx={{ mb: 2, mt: 1 }} SelectProps={{ native: true }}>
            <option value="">انتخاب درس...</option>
            {specs.map((s: any) => <option key={s.id} value={s.id}>{s.course?.name} ({s.course?.code})</option>)}
          </TextField>
          <TextField fullWidth label="موضوع" value={subject} onChange={(e) => setSubject(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="متن" multiline rows={3} value={body} onChange={(e) => setBody(e.target.value)} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={broadcast} disabled={!specId || !body}>ارسال</Button>
        </DialogActions>
      </Dialog>
      <Snackbar open={!!snack} autoHideDuration={5000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
