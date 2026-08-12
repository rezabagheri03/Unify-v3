import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import TextField from '@mui/material/TextField';
import MenuItem from '@mui/material/MenuItem';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

const DEPTS = [
  ['education', 'آموزش'], ['technical', 'فنی'], ['student_affairs', 'امور دانشجویی'],
];
const STATUS_COLOR: Record<string, any> = {
  open: 'default', in_progress: 'primary', answered: 'success', closed: 'default',
};

export default function TicketingList() {
  const [tickets, setTickets] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [dept, setDept] = useState('education');
  const [subject, setSubject] = useState('');
  const [description, setDescription] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    try {
      const res = await api.get('/tickets');
      setTickets(Array.isArray(res.data?.data) ? res.data.data : Array.isArray(res.data) ? res.data : []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => {
    load();
  }, []);

  const create = async () => {
    try {
      await api.post('/tickets', { department: dept, subject, description });
      setOpen(false);
      setSubject('');
      setDescription('');
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>تیکت‌های پشتیبانی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>تیکت جدید</Button>

      {tickets.map((t: any) => (
        <Card key={t.id} sx={{ mb: 1 }}>
          <CardContent>
            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
              <Typography variant="subtitle1">{t.subject}</Typography>
              <Chip size="small" label={t.status} color={STATUS_COLOR[t.status] || 'default'} />
            </Box>
            <Typography variant="body2" color="text.secondary">
              {t.department} • {new Date(t.created_at).toLocaleString('fa-IR')}
              {t.is_escalated && <Chip size="small" label="ارجاع شده" color="warning" sx={{ ml: 1 }} />}
            </Typography>
            <Typography variant="body2" sx={{ mt: 0.5 }}>{t.description}</Typography>
          </CardContent>
        </Card>
      ))}
      {tickets.length === 0 && <Typography color="text.secondary">تیکتی ثبت نشده</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>تیکت جدید</DialogTitle>
        <DialogContent>
          <TextField select fullWidth label="بخش" value={dept} onChange={(e) => setDept(e.target.value)} sx={{ mb: 2, mt: 1 }}>
            {DEPTS.map(([k, l]) => <MenuItem key={k} value={k}>{l}</MenuItem>)}
          </TextField>
          <TextField fullWidth label="موضوع (حداکثر ۱۰۰)" value={subject} onChange={(e) => setSubject(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="شرح (حداکثر ۲۰۰۰)" multiline rows={4} value={description} onChange={(e) => setDescription(e.target.value)} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={create} disabled={!subject || !description}>ثبت</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
