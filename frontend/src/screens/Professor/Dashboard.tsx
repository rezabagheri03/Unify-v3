import React, { useState } from 'react';
import { useAuthStore } from '../../stores/authStore';

export default function ProfessorDashboard() {
  const { user } = useAuthStore();
  const [stats] = useState({ students: 87, resources: 24, messages: 12 });

  return (
    <div style={{ padding: 24 }}>
      <h1>داشبورد استاد</h1>
      <p>خوش آمدید، {user?.first_name}</p>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16, marginTop: 24 }}>
        <div style={{ background: '#e3f2fd', padding: 20, borderRadius: 12 }}>
          <h3>👨‍🎓 دانشجویان</h3>
          <p style={{ fontSize: 32, margin: 0 }}>{stats.students}</p>
        </div>
        <div style={{ background: '#e8f5e9', padding: 20, borderRadius: 12 }}>
          <h3>📚 منابع</h3>
          <p style={{ fontSize: 32, margin: 0 }}>{stats.resources}</p>
        </div>
        <div style={{ background: '#fff3e0', padding: 20, borderRadius: 12 }}>
          <h3>✉️ پیام‌ها</h3>
          <p style={{ fontSize: 32, margin: 0 }}>{stats.messages}</p>
        </div>
      </div>
    </div>
  );
}