import React, { useState } from 'react';

export default function TicketsEscalated() {
  const [tickets] = useState([
    { id: 1, subject: 'مشکل در انتخاب واحد', level: 2 },
    { id: 2, subject: 'عدم دسترسی به منابع', level: 1 },
  ]);

  return (
    <div style={{ padding: 24 }}>
      <h2>تیکت‌های escalate شده ({tickets.length})</h2>
      
      {tickets.map(t => (
        <div key={t.id} style={{ padding: 12, border: '1px solid #ff9800', marginBottom: 8 }}>
          {t.subject} — سطح {t.level}
          <button style={{ marginLeft: 16 }}>بررسی</button>
        </div>
      ))}
    </div>
  );
}