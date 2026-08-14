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

const STATUS_COLOR: Record<string, any> = {
  pending: 'default', submitted: 'info', graded: 'success', late: 'error', missed: 'error',
};

export default function AssignmentTrackerList() {
  const [assignments, setAssignments] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [dueDateShamsi, setDueDateShamsi] = useState('');
  const [specificationId, setSpecificationId] = useState('');
  const [specs, setSpecs] = useState<any[]>([]);
  const [error, setError] = useState('');

  const load = async () => {
    try {
      const res = await api.get('/assignments');
      setAssignments(Array.isArray(res.data) ? res.data : []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => {
    load();
    api.get('/specifications').then((r) => setSpecs(r.data?.data || [])).catch(() => {});
  }, []);

  const create = async () => {
    try {
      await api.post('/assignments', { specification_id: specificationId, title, due_date_shamsi: dueDateShamsi });
      setOpen(false);
      setTitle('');
      setDueDateShamsi('');
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>تکالیف</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>تکلیف جدید</Button>
      {assignments.map((a: any) => (
        <Card key={a.id} sx={{ mb: 1 }}>
          <CardContent>
            <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
              <Typography variant="subtitle1">{a.title}</Typography>
              <Chip size="small" label={a.status} color={STATUS_COLOR[a.status] || 'default'} />
            </Box>
            <Typography variant="body2" color="text.secondary">
              مهلت: {a.shamsi_original || new Date(a.due_date_g).toLocaleDateString('fa-IR')}
              {a.grade !== null && a.grade !== undefined && <> • نمره: {a.grade}</>}
            </Typography>
          </CardContent>
        </Card>
      ))}
      {assignments.length === 0 && <Typography color="text.secondary">تکلیفی ثبت نشده</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>تکلیف جدید</DialogTitle>
        <DialogContent>
          <TextField select fullWidth label="درس" value={specificationId} onChange={(e) => setSpecificationId(e.target.value)} sx={{ mb: 2, mt: 1 }}>
            {specs.map((s: any) => (
              <MenuItem key={s.id} value={s.id}>{s.course?.name} ({s.course?.code})</MenuItem>
            ))}
          </TextField>
          <TextField fullWidth label="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="مهلت (شمسی YYYY/MM/DD)" value={dueDateShamsi} onChange={(e) => setDueDateShamsi(e.target.value)} placeholder="1403/08/15" />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={create} disabled={!title || !dueDateShamsi || !specificationId}>ثبت</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}


