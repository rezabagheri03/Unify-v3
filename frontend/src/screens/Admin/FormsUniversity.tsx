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
import api, { apiErrorMessage } from '../../api/client';

export default function FormsUniversity() {
  const [forms, setForms] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState('');

  const load = () => {
    api.get('/forms').then((res) => setForms(Array.isArray(res.data) ? res.data : []))
      .catch((err) => setError(apiErrorMessage(err)));
  };
  useEffect(() => { load(); }, []);

  const create = async () => {
    const fd = new FormData();
    fd.append('title', title);
    if (file) fd.append('file', file);
    fd.append('is_university_level', '1');
    try {
      await api.post('/forms', fd, { headers: { 'Content-Type': 'multipart/form-data' }, timeout: 240000 }); // V2-01: 20MB forms
      setOpen(false); setTitle(''); setFile(null);
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>فرم‌های دانشگاهی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>فرم دانشگاهی جدید</Button>
      {forms.filter((f: any) => f.is_university_level).map((f: any) => (
        <Box key={f.id} sx={{ p: 1, border: '1px solid #ddd', borderRadius: 1, mb: 1 }}>
          <Typography variant="subtitle1">{f.title}</Typography>
        </Box>
      ))}
      {forms.filter((f: any) => f.is_university_level).length === 0 && <Typography color="text.secondary">فرم دانشگاهی‌ای ثبت نشده</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>فرم دانشگاهی جدید</DialogTitle>
        <DialogContent>
          <TextField fullWidth label="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} sx={{ mb: 2, mt: 1 }} />
          <input type="file" accept=".pdf,.docx" onChange={(e) => setFile(e.target.files?.[0] || null)} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={create} disabled={!title || !file}>ثبت</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
