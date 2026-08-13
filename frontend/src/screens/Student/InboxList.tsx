import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Tabs from '@mui/material/Tabs';
import Tab from '@mui/material/Tab';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

const TABS = [
  // Post-audit F-13: the 'system' tab was removed — no code path ever creates
  // a both-null (system) message, so it could only ever render empty.
  ['all', 'همه'], ['unread', 'خوانده‌نشده'], ['classes', 'کلاس‌ها'], ['private', 'خصوصی'],
];

export default function InboxList() {
  const [tab, setTab] = useState('all');
  const [messages, setMessages] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [recipientId, setRecipientId] = useState('');
  const [error, setError] = useState('');

  const load = async (t: string) => {
    try {
      const res = await api.get('/messages', { params: { tab: t } });
      setMessages(res.data?.data || []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => {
    load(tab);
  }, [tab]);

  const markRead = async (id: string) => {
    try {
      await api.post(`/messages/${id}/read`);
      load(tab);
    } catch {
      // ignore
    }
  };

  const send = async () => {
    try {
      await api.post('/messages/send', { subject, body, recipient_id: recipientId || undefined });
      setOpen(false);
      setSubject('');
      setBody('');
      load(tab);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>پیام‌ها</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
        <Tabs value={tab} onChange={(_, v) => setTab(v)}>
          {TABS.map(([k, l]) => <Tab key={k} value={k} label={l} />)}
        </Tabs>
        <Button variant="contained" onClick={() => setOpen(true)}>پیام جدید</Button>
      </Box>

      {messages.map((m: any) => (
        <Card key={m.id} sx={{ mb: 1 }} onClick={() => markRead(m.id)}>
          <CardContent>
            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
              <Typography variant="subtitle1">
                {m.subject || '(بدون موضوع)'}
                {m.is_edited && <Chip size="small" label="ویرایش شده" sx={{ ml: 1 }} />}
                {m.is_deleted && <Chip size="small" label="حذف شده" color="error" sx={{ ml: 1 }} />}
              </Typography>
              <Chip size="small" label={m.priority || 'normal'} />
            </Box>
            <Typography variant="body2" color="text.secondary">
              {m.sender?.first_name} {m.sender?.last_name} • {new Date(m.sent_at).toLocaleString('fa-IR')}
            </Typography>
            <Typography variant="body2" sx={{ mt: 0.5 }}>{m.body}</Typography>
          </CardContent>
        </Card>
      ))}
      {messages.length === 0 && <Typography color="text.secondary">پیامی نیست</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>پیام جدید</DialogTitle>
        <DialogContent>
          <TextField fullWidth label="شناسه گیرنده (اختیاری برای پخش کلاسی)" value={recipientId} onChange={(e) => setRecipientId(e.target.value)} sx={{ mb: 2, mt: 1 }} />
          <TextField fullWidth label="موضوع" value={subject} onChange={(e) => setSubject(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="متن پیام" multiline rows={4} value={body} onChange={(e) => setBody(e.target.value)} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={send} disabled={!body}>ارسال</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
