import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';
import ThemePreview from '../../components/ThemePreview';

export default function BrandingLogo() {
  const [brand, setBrand] = useState('Unify');
  const [theme, setTheme] = useState('Unify Blue');
  const [file, setFile] = useState<File | null>(null);
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/branding').then((res) => {
      if (res.data?.brand_name) setBrand(res.data.brand_name);
    }).catch(() => {});
  }, []);

  const saveBrand = async () => {
    try {
      await api.post('/admin/branding/logo', file ? fileToForm() : new FormData());
      setSnack('برندینگ ذخیره شد');
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  const fileToForm = () => {
    const fd = new FormData();
    if (file) fd.append('logo', file);
    return fd;
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>برندینگ (F18)</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card sx={{ mb: 2 }}>
        <CardContent>
          <TextField label="نام برند" value={brand} onChange={(e) => setBrand(e.target.value)} sx={{ mb: 2 }} />
          <Box>
            <Typography variant="body2" sx={{ mb: 1 }}>آپلود لوگو (PNG/SVG حداکثر ۲MB)</Typography>
            <input type="file" accept=".png,.svg" onChange={(e) => setFile(e.target.files?.[0] || null)} />
          </Box>
        </CardContent>
      </Card>
      <Typography variant="subtitle2" gutterBottom>انتخاب تم</Typography>
      <ThemePreview active={theme} onSelect={setTheme} />
      <Button variant="contained" sx={{ mt: 2 }} onClick={saveBrand}>ذخیره برندینگ</Button>
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
