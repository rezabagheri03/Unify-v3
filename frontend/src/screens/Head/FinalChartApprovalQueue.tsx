import React, { useState } from 'react';

export default function FinalChartApprovalQueue() {
  const [queue, setQueue] = useState([
    { id: 1, title: 'نمودار ورودی ۱۴۰۱', status: 'pending' },
    { id: 2, title: 'نمودار ورودی ۱۴۰۲', status: 'pending' },
  ]);

  const approve = (id: number) => {
    setQueue(queue.map(q => q.id === id ? { ...q, status: 'approved' } : q));
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>صف تأیید نمودار درسی</h2>

      {queue.map(item => (
        <div key={item.id} style={{ 
          padding: 16, 
          border: '1px solid #ddd', 
          marginBottom: 12,
          background: item.status === 'approved' ? '#e8f5e9' : '#fff'
        }}>
          <strong>{item.title}</strong>
          <span style={{ marginLeft: 16, color: item.status === 'approved' ? 'green' : 'orange' }}>
            {item.status === 'approved' ? 'تأیید شده' : 'در انتظار'}
          </span>
          {item.status === 'pending' && (
            <button onClick={() => approve(item.id)} style={{ marginLeft: 16 }}>تأیید نهایی</button>
          )}
        </div>
      ))}
    </div>
  );
}