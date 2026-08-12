import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import MenuItem from '@mui/material/MenuItem';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';

export default function UploadCenter() {
  const [file, setFile] = useState<File | null>(null);
  const [title, setTitle] = useState('');
  const [courseId, setCourseId] = useState('');
  const [courses, setCourses] = useState<any[]>([]);
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/specifications')
      .then((res) => {
        const seen = new Map<string, any>();
        (res.data?.data || []).forEach((s: any) => seen.set(s.course?.code, s.course));
        setCourses([...seen.values()]);
      })
      .catch(() => {});
  }, []);

  const upload = async () => {
    if (!file || !title || !courseId) return;
    const fd = new FormData();
    fd.append('file', file);
    fd.append('title', title);
    fd.append('course_id', courseId);
    try {
      const res = await api.post('/resources/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      setSnack('آپلود شد (منبع استاد خودکار تأیید می‌شود)');
      setTitle('');
      setFile(null);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>آپلود منبع</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        PDF یا DOCX — حداکثر ۵۰ مگابایت. فایل استاد با نشان «استاد» به‌صورت خودکار تأیید می‌شود.
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card>
        <CardContent>
          <TextField select fullWidth label="درس" value={courseId} onChange={(e) => setCourseId(e.target.value)} sx={{ mb: 2 }}>
            {courses.map((c: any) => (
              <MenuItem key={c.code} value={c.code}>{c.name} ({c.code})</MenuItem>
            ))}
          </TextField>
          <TextField fullWidth label="عنوان" value={title} onChange={(e) => setTitle(e.target.value)} sx={{ mb: 2 }} />
          <input type="file" accept=".pdf,.docx" onChange={(e) => setFile(e.target.files?.[0] || null)} />
          <Button variant="contained" sx={{ ml: 2 }} onClick={upload} disabled={!file || !title || !courseId}>آپلود</Button>
        </CardContent>
      </Card>
      <Snackbar open={!!snack} autoHideDuration={5000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}


