import React, { useState } from 'react';

export default function UsersManagement() {
  const [search, setSearch] = useState('');
  const [users] = useState([
    { id: '400123456', name: 'علی احمدی', role: 'student', dept: 'کامپیوتر' },
    { id: '990001', name: 'دکتر کریمی', role: 'professor', dept: 'کامپیوتر' },
    { id: '880002', name: 'مهندس رضایی', role: 'expert', dept: 'آموزش' },
    { id: '770003', name: 'دکتر محمدی', role: 'head_of_dept', dept: 'کامپیوتر' },
  ]);

  const filtered = users.filter(u => 
    u.name.includes(search) || u.id.includes(search)
  );

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت کاربران</h2>
      
      <input 
        placeholder="جستجو..." 
        value={search} 
        onChange={e => setSearch(e.target.value)}
        style={{ padding: 10, width: '100%', maxWidth: 400, marginBottom: 16 }}
      />

      <table style={{ width: '100%', borderCollapse: 'collapse' }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>شماره</th>
            <th style={{ padding: 12, textAlign: 'right' }}>نام</th>
            <th style={{ padding: 12, textAlign: 'right' }}>نقش</th>
            <th style={{ padding: 12, textAlign: 'right' }}>گروه</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {filtered.map(user => (
            <tr key={user.id} style={{ borderBottom: '1px solid #eee' }}>
              <td style={{ padding: 12 }}>{user.id}</td>
              <td style={{ padding: 12 }}>{user.name}</td>
              <td style={{ padding: 12 }}>{user.role}</td>
              <td style={{ padding: 12 }}>{user.dept}</td>
              <td style={{ padding: 12 }}>
                <button style={{ marginRight: 8 }}>ویرایش</button>
                <button style={{ color: 'red' }}>بن</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}