import React, { useState } from 'react';

export default function FormsUniversity() {
  const [forms, setForms] = useState([
    { id: 1, title: 'فرم درخواست مرخصی', level: 'دانشگاه', active: true },
    { id: 2, title: 'فرم انتخاب واحد', level: 'دانشگاه', active: true },
    { id: 3, title: 'فرم اعتراض به نمره', level: 'دانشکده', active: true },
    { id: 4, title: 'فرم درخواست خوابگاه', level: 'دانشگاه', active: false },
  ]);

  const toggle = (id: number) => {
    setForms(forms.map(f => f.id === id ? { ...f, active: !f.active } : f));
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>فرم‌های دانشگاهی</h2>
      <button style={{ marginBottom: 16 }}>آپلود فرم جدید</button>

      <table style={{ width: '100%', maxWidth: 800 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>عنوان</th>
            <th style={{ padding: 12, textAlign: 'right' }}>سطح</th>
            <th style={{ padding: 12, textAlign: 'right' }}>وضعیت</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {forms.map(f => (
            <tr key={f.id}>
              <td style={{ padding: 12 }}>{f.title}</td>
              <td style={{ padding: 12 }}>{f.level}</td>
              <td style={{ padding: 12 }}>
                <span style={{ color: f.active ? 'green' : 'gray' }}>{f.active ? 'فعال' : 'غیرفعال'}</span>
              </td>
              <td style={{ padding: 12 }}>
                <button onClick={() => toggle(f.id)}>
                  {f.active ? 'غیرفعال کردن' : 'فعال کردن'}
                </button>
                <button style={{ marginLeft: 8 }}>دانلود</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}