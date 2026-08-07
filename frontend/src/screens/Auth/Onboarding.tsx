import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Box from '@mui/material/Box';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Typography from '@mui/material/Typography';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../stores/authStore';
import { homePathFor } from '../../utils/navigation';

export default function Onboarding() {
  const navigate = useNavigate();
  const { user, updateUser, setMustChangePassword } = useAuthStore();
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [newPasswordConf, setNewPasswordConf] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      await api.post('/onboarding', { first_name: firstName, last_name: lastName });
      await api.post('/password/change', {
        old_password: oldPassword,
        new_password: newPassword,
        new_password_confirmation: newPasswordConf,
      });
      updateUser({ first_name: firstName, last_name: lastName });
      setMustChangePassword(false);
      navigate(homePathFor(user?.role));
    } catch (err: any) {
      setError(apiErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box sx={{ maxWidth: 460, mx: 'auto', mt: 10, p: 3 }}>
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
          ذخیره و ورود
        </Button>
      </form>
    </Box>
  );
}
