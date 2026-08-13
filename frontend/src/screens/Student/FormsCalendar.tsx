import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Tabs from '@mui/material/Tabs';
import Tab from '@mui/material/Tab';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function FormsCalendar() {
  const [tab, setTab] = useState('forms');
  const [forms, setForms] = useState<any[]>([]);
  const [calendar, setCalendar] = useState<any[]>([]);
  const [noticeBoard, setNoticeBoard] = useState<any[]>([]);
  const [faqs, setFaqs] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    Promise.allSettled([
      api.get('/forms'),
      api.get('/academic-calendar'),
      api.get('/notice-board'),
      api.get('/faqs'),
    ]).then(([f, c, n, q]) => {
      setForms(f.status === 'fulfilled' && Array.isArray(f.value.data) ? f.value.data : []);
      setCalendar(c.status === 'fulfilled' && Array.isArray(c.value.data) ? c.value.data : []);
      setNoticeBoard(n.status === 'fulfilled' && Array.isArray(n.value.data) ? n.value.data : []);
      setFaqs(q.status === 'fulfilled' && Array.isArray(q.value.data) ? q.value.data : []);
    }).catch((err) => setError(apiErrorMessage(err)));
  }, []);

  const downloadForm = async (id: string) => {
    const res = await api.get(`/forms/${id}/download`, { responseType: 'blob', timeout: 120000 }); // V2-01
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a');
    a.href = url;
    a.download = `form-${id}.pdf`;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>فرم‌ها و تقویم</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 2 }}>
        <Tab value="forms" label="فرم‌ها" />
        <Tab value="calendar" label="تقویم" />
        <Tab value="notices" label="اعلان‌ها" />
        <Tab value="faqs" label="سوالات متداول" />
      </Tabs>

      {tab === 'forms' && forms.map((f: any) => (
        <Card key={f.id} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', justifyContent: 'space-between' }}>
            <Box>
              <Typography variant="subtitle1">{f.title}</Typography>
              <Typography variant="body2" color="text.secondary">{f.description}</Typography>
            </Box>
            <Button size="small" variant="contained" onClick={() => downloadForm(f.id)}>دانلود</Button>
          </CardContent>
        </Card>
      ))}
      {tab === 'forms' && forms.length === 0 && <Typography color="text.secondary">فرمی نیست</Typography>}

      {tab === 'calendar' && calendar.map((e: any) => (
        <Card key={e.id} sx={{ mb: 1 }}>
          <CardContent>
            <Typography variant="subtitle1">{e.title}</Typography>
            <Typography variant="body2" color="text.secondary">
              {new Date(e.start_date_g).toLocaleDateString('fa-IR')} — {new Date(e.end_date_g).toLocaleDateString('fa-IR')} • {e.event_type}
            </Typography>
          </CardContent>
        </Card>
      ))}
      {tab === 'calendar' && calendar.length === 0 && <Typography color="text.secondary">رویدادی نیست</Typography>}

      {tab === 'notices' && noticeBoard.map((n: any) => (
        <Card key={n.id} sx={{ mb: 1 }}>
          <CardContent>
            <Typography variant="subtitle1">{n.title}</Typography>
            <Typography variant="body2">{n.content}</Typography>
          </CardContent>
        </Card>
      ))}
      {tab === 'notices' && noticeBoard.length === 0 && <Typography color="text.secondary">اعلانی نیست</Typography>}

      {tab === 'faqs' && faqs.map((q: any) => (
        <Card key={q.id} sx={{ mb: 1 }}>
          <CardContent>
            <Typography variant="subtitle1">{q.question}</Typography>
            <Typography variant="body2">{q.answer}</Typography>
          </CardContent>
        </Card>
      ))}
      {tab === 'faqs' && faqs.length === 0 && <Typography color="text.secondary">سوالی نیست</Typography>}
    </Box>
  );
}
