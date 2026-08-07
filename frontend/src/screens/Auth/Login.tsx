import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Box from '@mui/material/Box';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Typography from '@mui/material/Typography';
import Alert from '@mui/material/Alert';
import CircularProgress from '@mui/material/CircularProgress';
import api, { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../stores/authStore';
import { homePathFor } from '../../utils/navigation';
import ServerBanner from '../../components/ServerBanner';

export default function Login() {
  const navigate = useNavigate();
  const location = useLocation() as any;
  const login = useAuthStore((s) => s.login);
  // Prefill username + show a notice when bounced back from onboarding (recovery).
  const prefilled = (location.state as any)?.username || '';
  const notice = (location.state as any)?.notice || '';
  const [username, setUsername] = useState(prefilled);
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const res = await api.post('/auth/login', { username, password });
      const { user, access_token } = res.data;
      login(user, access_token);
      // Pass along any typed onboarding details so the user doesn't retype them.
      const oState = (location.state as any)?.firstName ? (location.state as any) : undefined;
      navigate(user?.must_change_password ? '/onboarding' : homePathFor(user?.role), { state: oState });
    } catch (err: any) {
      setError(apiErrorMessage(err, 'نام کاربری یا رمز اشتباه است'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box sx={{ maxWidth: 420, mx: 'auto', mt: 12, p: 3 }}>
      <ServerBanner />
      <Typography variant="h4" align="center" gutterBottom>
        یونیفای
      </Typography>
      <Typography variant="body2" align="center" color="text.secondary" sx={{ mb: 1 }}>
        سامانه دستیار دانشگاهی — ورود با کد دانشجویی / پرسنلی
      </Typography>
      <Typography variant="caption" align="center" display="block" color="text.secondary" sx={{ mb: 3 }}>
        اگر رمز موقت خود را قبلاً تغییر داده‌اید، با رمز جدید وارد شوید.
      </Typography>
      {notice && (
        <Alert severity="info" sx={{ mb: 2 }}>
          {notice}
        </Alert>
      )}
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <form onSubmit={submit}>
        <TextField
          fullWidth label="شماره دانشجویی / پرسنلی" variant="outlined" sx={{ mb: 2 }}
          value={username} onChange={(e) => setUsername(e.target.value)} required autoFocus
        />
        <TextField
          fullWidth label="رمز عبور" type="password" variant="outlined" sx={{ mb: 3 }}
          value={password} onChange={(e) => setPassword(e.target.value)} required
        />
        <Button type="submit" fullWidth variant="contained" size="large" disabled={loading}>
          {loading ? <CircularProgress size={22} color="inherit" /> : 'ورود'}
        </Button>
      </form>
    </Box>
  );
}
