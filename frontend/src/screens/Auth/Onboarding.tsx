import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import Box from '@mui/material/Box';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Typography from '@mui/material/Typography';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../stores/authStore';
import { homePathFor } from '../../utils/navigation';
import ServerBanner from '../../components/ServerBanner';

export default function Onboarding() {
  const navigate = useNavigate();
  const location = useLocation() as any;
  const { user, updateUser, setMustChangePassword } = useAuthStore();
  // Prefill from a previous interrupted attempt (names survive a re-login).
  const prevState = (location.state as any) || {};
  const [firstName, setFirstName] = useState(prevState.firstName || '');
  const [lastName, setLastName] = useState(prevState.lastName || '');
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConf, setNewPasswordConf] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    // Client-side password sanity before hitting the API
    if (newPassword.length < 8) {
      setError('رمز جدید باید حداقل ۸ کاراکتر باشد');
      setLoading(false);
      return;
    }
    if (newPassword !== newPasswordConf) {
      setError('تکرار رمز جدید با رمز جدید یکسان نیست');
      setLoading(false);
      return;
    }
    if (newPassword === oldPassword) {
      setError('رمز جدید نباید با رمز فعلی یکسان باشد');
      setLoading(false);
      return;
    }

    try {
      // Step 1 (CRITICAL): change the temp password — this activates the account.
      await api.post('/password/change', {
        old_password: oldPassword,
        new_password: newPassword,
        new_password_confirmation: newPasswordConf,
      });

      // Step 2 (informational): save the name. If this fails, the account is
      // still activated — don't block or confuse the user.
      try {
        await api.post('/onboarding', { first_name: firstName, last_name: lastName });
      } catch (nameErr: any) {
        console.warn('name save failed (non-fatal):', nameErr?.response?.status, nameErr?.message);
      }

      updateUser({ first_name: firstName, last_name: lastName });
      setMustChangePassword(false);
      navigate(homePathFor(user?.role));
    } catch (err: any) {
      // Session lost (token wiped by a reload/iframe remount in the sandboxed
      // preview). The password change did NOT happen, so the temp password is
      // still valid — send the user back to login prefilled to retry.
      if (err?.response?.status === 401) {
        navigate('/login', {
          state: {
            username: user?.id || '',
            firstName,
            lastName,
            notice: 'نشست شما منقضی شد (صفحه تازه‌سازی شد). دوباره با رمز موقت وارد شوید — رمز هنوز تغییر نکرده است.',
          },
        });
        return;
      }
      const msg = apiErrorMessage(err);
      // Explain the most common failure so the user isn't left guessing.
      if (err?.response?.status === 422) {
        const field = err?.response?.data?.errors;
        if (field?.new_password) {
          setError('رمز جدید نامعتبر است: ' + (field.new_password[0] || ''));
        } else if (field?.old_password) {
          setError('رمز موقت فعلی را درست وارد کنید');
        } else {
          setError(msg);
        }
      } else if (err?.response?.status === 400) {
        setError('تغییر رمز ناموفق: ' + msg + ' — رمز موقت قبلی هنوز معتبر است.');
      } else {
        setError(msg);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box sx={{ maxWidth: 460, mx: 'auto', mt: 10, p: 3 }}>
      <ServerBanner />
      <Typography variant="h5" align="center" gutterBottom>
        تکمیل اطلاعات اولیه
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }} align="center">
        {user?.id ? `شماره دانشجویی: ${user.id}` : ''} — طبق سیاست فیزیکی IT، رمز موقت باید تغییر کند.
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <form onSubmit={submit}>
        <TextField fullWidth label="نام" sx={{ mb: 2 }} value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
        <TextField fullWidth label="نام خانوادگی" sx={{ mb: 2 }} value={lastName} onChange={(e) => setLastName(e.target.value)} required />
        <TextField fullWidth label="رمز موقت فعلی" type="password" sx={{ mb: 2 }} value={oldPassword} onChange={(e) => setOldPassword(e.target.value)} required />
        <TextField fullWidth label="رمز جدید (حداقل ۸ کاراکتر، شامل عدد و نماد)" type="password" sx={{ mb: 2 }} value={newPassword} onChange={(e) => setNewPassword(e.target.value)} required />
        <TextField fullWidth label="تکرار رمز جدید" type="password" sx={{ mb: 3 }} value={newPasswordConf} onChange={(e) => setNewPasswordConf(e.target.value)} required />
        <Button type="submit" fullWidth variant="contained" size="large" disabled={loading}>
          {loading ? 'در حال ثبت...' : 'ذخیره و ورود'}
        </Button>
      </form>
    </Box>
  );
}
