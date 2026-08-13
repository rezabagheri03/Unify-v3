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
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

const LANE_LABEL: Record<string, string> = {
  education: 'آموزش', technical: 'فنی', student_affairs: 'امور دانشجویی',
};
const STATUS_COLOR: Record<string, any> = {
  open: 'default', in_progress: 'primary', answered: 'success', closed: 'default',
};

/**
 * Expert ticket lane (Round-2, V2-02): the API scopes this list to the
 * caller's users.ticket_lane; cross-lane read/reply/status return 403.
 */
export default function TicketsLane() {
  const [tickets, setTickets] = useState<any[]>([]);
  const [active, setActive] = useState<any | null>(null);
  const [reply, setReply] = useState('');
  const [snack, setSnack] = useState('');
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

  const act = async (fn: () => Promise<any>, ok: string) => {
    try {
      await fn();
      setSnack(ok);
      setReply('');
      setActive(null);
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>تیکت‌های زمینه من</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {tickets.length === 0 && (
        <Typography color="text.secondary">تیکتی در زمینه شما ثبت نشده است.</Typography>
      )}
      {tickets.map((t) => (
        <Card key={t.id} sx={{ mb: 1.5 }}>
          <CardContent sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Chip size="small" label={LANE_LABEL[t.department] || t.department} />
            <Box sx={{ flex: 1 }}>
              <Typography variant="subtitle2">{t.subject}</Typography>
              <Typography variant="caption" color="text.secondary">
                {t.student ? `${t.student.first_name} ${t.student.last_name}` : t.student_id}
              </Typography>
            </Box>
            <Chip size="small" color={STATUS_COLOR[t.status] || 'default'} label={t.status} />
            <Button size="small" onClick={() => setActive(t)}>مشاهده</Button>
          </CardContent>
        </Card>
      ))}

      <Dialog open={!!active} onClose={() => setActive(null)} maxWidth="sm" fullWidth>
        <DialogTitle>{active?.subject}</DialogTitle>
        <DialogContent>
          <Typography variant="body2" sx={{ mb: 2, whiteSpace: 'pre-wrap' }}>{active?.description}</Typography>
          {(active?.replies || []).map((r: any, i: number) => (
            <Typography key={i} variant="caption" display="block" sx={{ mb: 0.5 }}>
              {r.is_staff ? '👤 کارشناس' : '🎓 دانشجو'}: {r.body}
            </Typography>
          ))}
          <TextField
            fullWidth multiline rows={3} label="پاسخ"
            value={reply} onChange={(e) => setReply(e.target.value)} sx={{ mt: 1 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setActive(null)}>بستن پنجره</Button>
          {active?.status !== 'closed' && (
            <>
              <Button onClick={() => act(() => api.patch(`/tickets/${active.id}/status`, { status: 'in_progress' }), 'در حال بررسی شد')}>
                در حال بررسی
              </Button>
              <Button
                variant="contained"
                disabled={!reply.trim()}
                onClick={() => act(() => api.post(`/tickets/${active.id}/reply`, { body: reply }), 'پاسخ ارسال شد')}
              >
                ارسال پاسخ
              </Button>
            </>
          )}
        </DialogActions>
      </Dialog>
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
