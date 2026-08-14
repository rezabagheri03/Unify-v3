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
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function CoursesCRUD() {
  const [courses, setCourses] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [code, setCode] = useState('');
  const [name, setName] = useState('');
  const [credits, setCredits] = useState('3');
  const [error, setError] = useState('');
  const [snack, setSnack] = useState('');

  const load = () => {
    // Course list via specifications is indirect; we read specs' courses.
    api.get('/specifications').then((res) => {
      const map = new Map<string, any>();
      (res.data?.data || []).forEach((s: any) => s.course && map.set(s.course.code, s.course));
      setCourses([...map.values()]);
    }).catch((err) => setError(apiErrorMessage(err)));
  };
  useEffect(() => { load(); }, []);

  const create = async () => {
    try {
      // Course creation is not exposed via REST in this backend yet; use import for bulk.
      // Keep UI honest: instruct to use Excel import for course creation.
      setSnack('ایجاد درس از طریق ایمپورت اکسل (صفحه ایمپورت) انجام می‌شود');
      setOpen(false);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>دروس</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>درس جدید (اکسل)</Button>
      {courses.map((c: any) => (
        <Card key={c.code} sx={{ mb: 1 }}>
          <CardContent>
            <Typography variant="subtitle1">{c.name} ({c.code})</Typography>
            <Typography variant="body2" color="text.secondary">{c.credits} واحد</Typography>
          </CardContent>
        </Card>
      ))}
      {courses.length === 0 && <Typography color="text.secondary">درسی ثبت نشده</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)}>
        <DialogTitle>ایجاد درس</DialogTitle>
        <DialogContent>
          <Typography variant="body2" sx={{ mb: 2 }}>
            برای ایجاد/ویرایش انبوه دروس از «ایمپورت اکسل» استفاده کنید (تراکنشی با گزارش خطا).
          </Typography>
          <TextField fullWidth label="کد درس" value={code} onChange={(e) => setCode(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="نام درس" value={name} onChange={(e) => setName(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="واحد" value={credits} onChange={(e) => setCredits(e.target.value)} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={create}>به ایمپورت هدایت شود</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
