import React from 'react';

export default function AnalyticsLimited() {
  return (
    <div style={{ padding: 24 }}>
      <h2>آنالیتیکس محدود</h2>
      
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16 }}>
        <div style={{ background: '#e3f2fd', padding: 20, borderRadius: 8 }}>
          <h4>کاربران فعال امروز</h4>
          <p style={{ fontSize: 28 }}>124</p>
        </div>
        <div style={{ background: '#e8f5e9', padding: 20, borderRadius: 8 }}>
          <h4>تیکت‌های جدید</h4>
          <p style={{ fontSize: 28 }}>18</p>
        </div>
        <div style={{ background: '#fff3e0', padding: 20, borderRadius: 8 }}>
          <h4>منابع آپلود شده امروز</h4>
          <p style={{ fontSize: 28 }}>7</p>
        </div>
      </div>
    </div>
  );
}