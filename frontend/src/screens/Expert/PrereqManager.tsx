import React, { useState } from 'react';

export default function PrereqManager() {
  const [prereqs, setPrereqs] = useState([
    { id: 1, course: 'CS201', prereq: 'CS101' },
    { id: 2, course: 'CS301', prereq: 'CS201' },
    { id: 3, course: 'CS401', prereq: 'CS301' },
  ]);

  const addPrereq = () => {
    const newId = Math.max(...prereqs.map(p => p.id)) + 1;
    setPrereqs([...prereqs, { id: newId, course: 'CS' + newId + '01', prereq: 'CS' + (newId - 1) + '01' }]);
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت پیش‌نیازها</h2>
      <button onClick={addPrereq} style={{ marginBottom: 16 }}>افزودن پیش‌نیاز</button>

      <table style={{ width: '100%', maxWidth: 600 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>درس</th>
            <th style={{ padding: 12, textAlign: 'right' }}>پیش‌نیاز</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {prereqs.map((p, index) => (
            <tr key={index}>
              <td style={{ padding: 12 }}>{p.course}</td>
              <td style={{ padding: 12 }}>{p.prereq}</td>
              <td style={{ padding: 12 }}>
                <button>حذف</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}