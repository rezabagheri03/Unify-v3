import React from 'react';

export default function AnalyticsFull() {
  return (
    <div style={{ padding: 24 }}>
      <h2>آنالیتیکس کامل</h2>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16 }}>
        <div style={{ background: '#e3f2fd', padding: 20, borderRadius: 8 }}>
          <h4>کاربران فعال</h4>
          <p style={{ fontSize: 32 }}>587</p>
        </div>
        <div style={{ background: '#e8f5e9', padding: 20, borderRadius: 8 }}>
          <h4>منابع آپلود شده</h4>
          <p style={{ fontSize: 32 }}>1,243</p>
        </div>
        <div style={{ background: '#fff3e0', padding: 20, borderRadius: 8 }}>
          <h4>تیکت‌های حل شده</h4>
          <p style={{ fontSize: 32 }}>312</p>
        </div>
        <div style={{ background: '#fce4ec', padding: 20, borderRadius: 8 }}>
          <h4>حجم ذخیره‌سازی</h4>
          <p style={{ fontSize: 32 }}>34.2 GB</p>
        </div>
      </div>
    </div>
  );
}