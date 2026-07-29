import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../stores/authStore';
import api from '../../api/client';

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const { login } = useAuthStore();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    try {
      const res = await api.post('/auth/login', { username, password });
      
      login(res.data.user, res.data.access_token);
      
      if (res.data.must_change_password) {
        navigate('/onboarding');
      } else {
        navigate('/dashboard');
      }
    } catch (err: any) {
      if (!err.response) {
        setError('اتصال به سرور برقرار نشد. لطفاً backend را بررسی کنید.');
        return;
      }
      if (err.response.status === 429) {
        setError('تعداد تلاش‌های ورود زیاد است. لطفاً چند دقیقه صبر کنید.');
        return;
      }
      setError(err.response?.data?.message || 'خطا در ورود');
    }
  };

  return (
    <div style={{ padding: 40, maxWidth: 400, margin: '0 auto' }}>
      <h1>Unify V9</h1>
      <h2>ورود به سیستم</h2>
      
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          placeholder="شماره دانشجویی / شناسه"
          value={username}
          onChange={(e) => setUsername(e.target.value)}
          required
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <input
          type="password"
          placeholder="رمز عبور"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          style={{ width: '100%', padding: 12, marginBottom: 12 }}
        />
        <button type="submit" style={{ width: '100%', padding: 12 }}>
          ورود
        </button>
      </form>
      
      {error && <p style={{ color: 'red' }}>{error}</p>}
      
      <p style={{ marginTop: 20, fontSize: 12, color: '#666' }}>
        نسخه ۹.۰ - آماده برای Pars Pack Cloud Host (50GB Evergreen)
      </p>
    </div>
  );
}