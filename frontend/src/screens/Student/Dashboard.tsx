import React from 'react';
import { useAuthStore } from '../../stores/authStore';

export default function StudentDashboard() {
  const { user } = useAuthStore();

  return (
    <div style={{ padding: 24 }}>
      <h1>داشبورد دانشجو</h1>
      <p>خوش آمدید، {user?.first_name} {user?.last_name}</p>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 20, marginTop: 30 }}>
        <div style={{ background: '#f8f9fa', padding: 20, borderRadius: 12 }}>
          <h3>📅 انتخاب واحد</h3>
          <p>فاز A / B / C</p>
          <a href="/scheduler-a">رفتن به Scheduler</a>
        </div>

        <div style={{ background: '#f8f9fa', padding: 20, borderRadius: 12 }}>
          <h3>📚 مرکز منابع</h3>
          <p>منابع Evergreen</p>
          <a href="/resources">مشاهده منابع</a>
        </div>

        <div style={{ background: '#f8f9fa', padding: 20, borderRadius: 12 }}>
          <h3>🔔 اعلان‌ها</h3>
          <p>Polling هر ۳۰ ثانیه</p>
        </div>
      </div>

      {user?.academic_status_declared && (
        <div style={{ marginTop: 30, padding: 16, background: '#fff3cd', borderRadius: 8 }}>
          وضعیت تحصیلی اعلام شده: <strong>{user.academic_status_declared}</strong>
        </div>
      )}
    </div>
  );
}