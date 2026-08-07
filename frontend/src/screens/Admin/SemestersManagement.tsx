import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function SemestersManagement() {
  const [name, setName] = useState('');
  const [startShamsi, setStartShamsi] = useState('');
  const [endShamsi, setEndShamsi] = useState('');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const create = async () => {
    try {
      await api.post('/admin/semesters', { name, start_shamsi: startShamsi, end_shamsi: endShamsi });
      setSnack('نیم‌سال جدید ایجاد شد');
      setName(''); setStartShamsi(''); setEndShamsi('');
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>مدیریت نیم‌سال</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <TextField fullWidth label="نام نیم‌سال (مثل 1404-1)" value={name} onChange={(e) => setName(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="شروع (شمسی YYYY/MM/DD)" value={startShamsi} onChange={(e) => setStartShamsi(e.target.value)} sx={{ mb: 2 }} />
          <TextField fullWidth label="پایان (شمسی YYYY/MM/DD)" value={endShamsi} onChange={(e) => setEndShamsi(e.target.value)} sx={{ mb: 2 }} />
          <Button variant="contained" onClick={create} disabled={!name || !startShamsi || !endShamsi}>
            ایجاد نیم‌سال جدید
          </Button>
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
