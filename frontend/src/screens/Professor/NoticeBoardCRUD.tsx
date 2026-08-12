import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import TextField from '@mui/material/TextField';
import MenuItem from '@mui/material/MenuItem';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import Banner from '../../components/Banner';

export default function NoticeBoardCRUD() {
  const [notices, setNotices] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [priority, setPriority] = useState('medium');
  const [error, setError] = useState('');

  const load = () => {
    api.get('/notice-board').then((res) => setNotices(Array.isArray(res.data) ? res.data : []))
      .catch((err) => setError(apiErrorMessage(err)));
  };
  useEffect(() => { load(); }, []);

  const create = async () => {
    try {
      await api.post('/notice-board', { title, content, priority });
      setOpen(false); setTitle(''); setContent('');
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>اعلان‌ها</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>اعلان جدید</Button>
      {notices.map((n: any) => (
        <Banner key={n.id} tone={n.priority === 'high' ? 'critical' : n.priority === 'low' ? 'info' : 'warning'}>
          <strong>{n.title}</strong> — {n.content}
        </Banner>
      ))}
      {notices.length === 0 && <Typography color="text.secondary">اعلانی ثبت نشده</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>اعلان جدید</DialogTitle>
        <DialogContent>
          <TextField fullWidth label="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} sx={{ mb: 2, mt: 1 }} />
          <TextField fullWidth label="متن" multiline rows={3} value={content} onChange={(e) => setContent(e.target.value)} sx={{ mb: 2 }} />
          <TextField select fullWidth label="اولویت" value={priority} onChange={(e) => setPriority(e.target.value)}>
            {[['low', 'کم'], ['medium', 'متوسط'], ['high', 'بالا']].map(([k, l]) => <MenuItem key={k} value={k}>{l}</MenuItem>)}
          </TextField>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={create} disabled={!title || !content}>ثبت</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
