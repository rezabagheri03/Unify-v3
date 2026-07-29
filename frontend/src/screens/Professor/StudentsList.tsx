import React, { useState } from 'react';

export default function StudentsList() {
  const [students] = useState([
    { id: '400123456', name: 'علی احمدی', course: 'برنامه‌نویسی وب', grade: '18.5' },
    { id: '400123457', name: 'سارا محمدی', course: 'برنامه‌نویسی وب', grade: '17' },
    { id: '400123458', name: 'رضا کریمی', course: 'هوش مصنوعی', grade: '15.5' },
  ]);

  return (
    <div style={{ padding: 24 }}>
      <h2>لیست دانشجویان</h2>
      
      <table style={{ width: '100%', maxWidth: 700 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>شماره دانشجویی</th>
            <th style={{ padding: 12, textAlign: 'right' }}>نام</th>
            <th style={{ padding: 12, textAlign: 'right' }}>درس</th>
            <th style={{ padding: 12, textAlign: 'right' }}>نمره</th>
          </tr>
        </thead>
        <tbody>
          {students.map(s => (
            <tr key={s.id}>
              <td style={{ padding: 12 }}>{s.id}</td>
              <td style={{ padding: 12 }}>{s.name}</td>
              <td style={{ padding: 12 }}>{s.course}</td>
              <td style={{ padding: 12 }}>{s.grade}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}