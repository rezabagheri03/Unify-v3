import React, { useState } from 'react';

export default function SpecificationsCRUD() {
  const [specs] = useState([
    { id: 's1', course: 'CS101', day: 'شنبه', time: '08:00-10:00', professor: 'دکتر کریمی' },
    { id: 's2', course: 'CS201', day: 'دوشنبه', time: '10:00-12:00', professor: 'دکتر رضایی' },
    { id: 's3', course: 'CS301', day: 'سه‌شنبه', time: '14:00-16:00', professor: 'دکتر محمدی' },
  ]);

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت مشخصات درسی</h2>
      <button style={{ marginBottom: 16 }}>مشخصات جدید</button>

      <table style={{ width: '100%', maxWidth: 800 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>درس</th>
            <th style={{ padding: 12, textAlign: 'right' }}>روز</th>
            <th style={{ padding: 12, textAlign: 'right' }}>ساعت</th>
            <th style={{ padding: 12, textAlign: 'right' }}>استاد</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {specs.map((spec, index) => (
            <tr key={index}>
              <td style={{ padding: 12 }}>{spec.course}</td>
              <td style={{ padding: 12 }}>{spec.day}</td>
              <td style={{ padding: 12 }}>{spec.time}</td>
              <td style={{ padding: 12 }}>{spec.professor}</td>
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