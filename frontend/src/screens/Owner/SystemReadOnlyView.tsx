import React from 'react';

export default function SystemReadOnlyView() {
  return (
    <div style={{ padding: 24 }}>
      <h2>نمای فقط خواندنی سیستم</h2>
      <p style={{ color: '#666' }}>این بخش فقط برای مشاهده است</p>

      <div style={{ marginTop: 20, background: '#f9f9f9', padding: 20, borderRadius: 8 }}>
        <h4>وضعیت کلی سیستم</h4>
        <ul>
          <li>تعداد کاربران فعال: 587</li>
          <li>تعداد منابع: 1,243</li>
          <li>حجم استفاده شده: 34.2 GB / 50 GB</li>
          <li>وضعیت: عادی</li>
        </ul>
      </div>
    </div>
  );
}