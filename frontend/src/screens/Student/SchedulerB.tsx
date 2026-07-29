import React from 'react';

export default function SchedulerB() {
  return (
    <div style={{ padding: 24 }}>
      <h2>فاز B - جدول هفتگی</h2>
      <p>نمایش جدول Sat-Wed با رنگ‌بندی</p>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(6, 1fr)', gap: 4 }}>
        {['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه'].map(day => (
          <div key={day} style={{ background: '#e3f2fd', padding: 8, textAlign: 'center' }}>{day}</div>
        ))}
      </div>
    </div>
  );
}