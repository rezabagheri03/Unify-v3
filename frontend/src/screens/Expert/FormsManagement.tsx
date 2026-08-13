import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import TextField from '@mui/material/TextField';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function FormsManagement() {
  const [forms, setForms] = useState<any[]>([]);
  const [open, setOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [univ, setUniv] = useState(false);
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const load = () => {
    api.get('/forms').then((res) => setForms(Array.isArray(res.data) ? res.data : []))
      .catch((err) => setError(apiErrorMessage(err)));
  };
  useEffect(() => { load(); }, []);

  const create = async () => {
    if (!file) return;
    const fd = new FormData();
    fd.append('title', title);
    fd.append('description', description);
    fd.append('file', file);
    fd.append('is_university_level', univ ? '1' : '0');
    try {
      await api.post('/forms', fd, { headers: { 'Content-Type': 'multipart/form-data' }, timeout: 240000 }); // V2-01: 20MB forms
      setSnack('فرم ثبت شد');
      setOpen(false); setTitle(''); setDescription(''); setFile(null);
      load();
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>فرم‌ها</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Button variant="contained" onClick={() => setOpen(true)} sx={{ mb: 2 }}>فرم جدید</Button>
      {forms.map((f: any) => (
        <Box key={f.id} sx={{ p: 1, border: '1px solid #ddd', borderRadius: 1, mb: 1 }}>
          <Typography variant="subtitle1">{f.title} {f.is_university_level ? '(دانشگاهی)' : ''}</Typography>
          <Typography variant="body2" color="text.secondary">{f.description}</Typography>
        </Box>
      ))}
      {forms.length === 0 && <Typography color="text.secondary">فرمی ثبت نشده</Typography>}

      <Dialog open={open} onClose={() => setOpen(false)} fullWidth>
        <DialogTitle>فرم جدید</DialogTitle>
        <DialogContent>
          <TextField fullWidth label="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} sx={{ mb: 2, mt: 1 }} />
          <TextField fullWidth label="توضیح" multiline rows={2} value={description} onChange={(e) => setDescription(e.target.value)} sx={{ mb: 2 }} />
          <input type="file" accept=".pdf,.docx" onChange={(e) => setFile(e.target.files?.[0] || null)} />
          <FormControlLabel control={<Checkbox checked={univ} onChange={(e) => setUniv(e.target.checked)} />} label="فرم دانشگاهی (برای همه)" />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpen(false)}>انصراف</Button>
          <Button variant="contained" onClick={create} disabled={!title || !file}>ثبت</Button>
        </DialogActions>
      </Dialog>
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
