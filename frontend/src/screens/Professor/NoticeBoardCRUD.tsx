import React, { useState } from 'react';

export default function NoticeBoardCRUD() {
  const [notices, setNotices] = useState([
    { id: 1, title: 'کلاس فردا لغو شد', priority: 'high', date: '۱۴۰۳/۰۵/۱۰' },
    { id: 2, title: 'جلسه دفاع', priority: 'medium', date: '۱۴۰۳/۰۵/۱۲' },
  ]);

  const addNotice = () => {
    const newNotice = {
      id: Date.now(),
      title: 'اعلان جدید',
      priority: 'medium',
      date: '۱۴۰۳/۰۵/۱۵'
    };
    setNotices([...notices, newNotice]);
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت تابلو اعلانات</h2>
      <button onClick={addNotice} style={{ marginBottom: 16 }}>اعلان جدید</button>

      <table style={{ width: '100%', maxWidth: 700 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>عنوان</th>
            <th style={{ padding: 12, textAlign: 'right' }}>اولویت</th>
            <th style={{ padding: 12, textAlign: 'right' }}>تاریخ</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {notices.map(n => (
            <tr key={n.id}>
              <td style={{ padding: 12 }}>{n.title}</td>
              <td style={{ padding: 12 }}>
                <span style={{ color: n.priority === 'high' ? 'red' : 'orange' }}>{n.priority}</span>
              </td>
              <td style={{ padding: 12 }}>{n.date}</td>
              <td style={{ padding: 12 }}>
                <button>ویرایش</button>
                <button style={{ marginLeft: 8, color: 'red' }}>حذف</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}