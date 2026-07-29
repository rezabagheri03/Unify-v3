import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../../api/client';
import { useAuthStore } from '../../stores/authStore';

export default function Onboarding() {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const { setMustChangePassword, updateUser } = useAuthStore();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (newPassword !== confirmPassword) {
      setError('رمز عبور جدید و تکرار آن یکسان نیست');
      return;
    }

    try {
      await api.post('/onboarding', { first_name: firstName, last_name: lastName });
      await api.post('/password/change', {
        old_password: oldPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });

      updateUser({
        first_name: firstName,
        last_name: lastName,
        must_change_password: false,
      });
      setMustChangePassword(false);
      navigate('/dashboard');
    } catch (err: any) {
      setError(err.response?.data?.message || 'خطا در تکمیل آنبوردینگ');
    }
  };

  return (
    <div style={{ padding: 40, maxWidth: 400, margin: '0 auto' }}>
      <h2>تکمیل اطلاعات</h2>
      <p>لطفاً اطلاعات خود را تکمیل کرده و رمز عبور موقت را تغییر دهید</p>
      
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          placeholder="نام"
          value={firstName}
          onChange={e => setFirstName(e.target.value)}
          required
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <input
          type="text"
          placeholder="نام خانوادگی"
          value={lastName}
          onChange={e => setLastName(e.target.value)}
          required
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <input
          type="password"
          placeholder="رمز عبور موقت فعلی"
          value={oldPassword}
          onChange={e => setOldPassword(e.target.value)}
          required
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <input
          type="password"
          placeholder="رمز عبور جدید"
          value={newPassword}
          onChange={e => setNewPassword(e.target.value)}
          required
          minLength={8}
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <input
          type="password"
          placeholder="تکرار رمز عبور جدید"
          value={confirmPassword}
          onChange={e => setConfirmPassword(e.target.value)}
          required
          minLength={8}
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <button type="submit" style={{ width: '100%', padding: 12 }}>
          ادامه
        </button>
      </form>

      {error && <p style={{ color: 'red' }}>{error}</p>}
    </div>
  );
}