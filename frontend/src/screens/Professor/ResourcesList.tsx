import React, { useState } from 'react';

export default function ProfessorResourcesList() {
  const [resources] = useState([
    { id: 1, title: 'جزوه برنامه‌نویسی.pdf', downloads: 145, rating: 4.8 },
    { id: 2, title: 'اسلایدهای درس.pptx', downloads: 89, rating: 4.5 },
    { id: 3, title: 'تمرین‌های حل شده.pdf', downloads: 203, rating: 4.9 },
  ]);

  return (
    <div style={{ padding: 24 }}>
      <h2>منابع من</h2>
      
      <table style={{ width: '100%', maxWidth: 700 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>عنوان</th>
            <th style={{ padding: 12, textAlign: 'right' }}>دانلود</th>
            <th style={{ padding: 12, textAlign: 'right' }}>امتیاز</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {resources.map(r => (
            <tr key={r.id}>
              <td style={{ padding: 12 }}>{r.title}</td>
              <td style={{ padding: 12 }}>{r.downloads}</td>
              <td style={{ padding: 12 }}>{r.rating}</td>
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