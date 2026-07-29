import React, { useState } from 'react';

export default function FormsManagement() {
  const [forms, setForms] = useState([
    { id: 1, title: 'درخواست مرخصی', dept: 'آموزش', active: true },
    { id: 2, title: 'اعتراض به نمره', dept: 'آموزش', active: true },
    { id: 3, title: 'درخواست خوابگاه', dept: 'امور دانشجویی', active: false },
  ]);

  const toggle = (id: number) => {
    setForms(forms.map(f => f.id === id ? { ...f, active: !f.active } : f));
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت فرم‌ها</h2>
      <button style={{ marginBottom: 16 }}>فرم جدید</button>

      <table style={{ width: '100%', maxWidth: 700 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>عنوان فرم</th>
            <th style={{ padding: 12, textAlign: 'right' }}>گروه</th>
            <th style={{ padding: 12, textAlign: 'right' }}>وضعیت</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {forms.map(f => (
            <tr key={f.id}>
              <td style={{ padding: 12 }}>{f.title}</td>
              <td style={{ padding: 12 }}>{f.dept}</td>
              <td style={{ padding: 12 }}>
                <span style={{ color: f.active ? 'green' : 'gray' }}>{f.active ? 'فعال' : 'غیرفعال'}</span>
              </td>
              <td style={{ padding: 12 }}>
                <button onClick={() => toggle(f.id)}>{f.active ? 'غیرفعال' : 'فعال'}</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}