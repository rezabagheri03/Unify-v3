import React from 'react';

export default function ProfessorOversight() {
  const [professors] = useState([
    { id: 1, name: 'دکتر کریمی', courses: 4, resources: 28, rating: 4.7 },
    { id: 2, name: 'دکتر رضایی', courses: 3, resources: 15, rating: 4.2 },
    { id: 3, name: 'دکتر محمدی', courses: 5, resources: 42, rating: 4.9 },
  ]);

  return (
    <div style={{ padding: 24 }}>
      <h2>نظارت بر اساتید</h2>

      <table style={{ width: '100%', maxWidth: 800 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>استاد</th>
            <th style={{ padding: 12, textAlign: 'right' }}>تعداد درس</th>
            <th style={{ padding: 12, textAlign: 'right' }}>منابع</th>
            <th style={{ padding: 12, textAlign: 'right' }}>امتیاز</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {professors.map(p => (
            <tr key={p.id}>
              <td style={{ padding: 12 }}>{p.name}</td>
              <td style={{ padding: 12 }}>{p.courses}</td>
              <td style={{ padding: 12 }}>{p.resources}</td>
              <td style={{ padding: 12 }}>{p.rating}</td>
              <td style={{ padding: 12 }}>
                <button>جزئیات</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}