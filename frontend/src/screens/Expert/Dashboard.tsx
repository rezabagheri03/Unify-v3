import React from 'react';

export default function ExpertDashboard() {
  return (
    <div style={{ padding: 24 }}>
      <h1>داشبورد کارشناس</h1>
      <div style={{ display: 'grid', gap: 16, marginTop: 24 }}>
        <div style={{ padding: 16, background: '#f5f5f5', borderRadius: 8 }}>
          <h3>منابع در انتظار تأیید: ۱۲</h3>
        </div>
        <div style={{ padding: 16, background: '#f5f5f5', borderRadius: 8 }}>
          <h3>تیکت‌های باز: ۳۴</h3>
        </div>
      </div>
    </div>
  );
}