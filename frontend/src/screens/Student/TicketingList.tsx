import React, { useState, useEffect } from 'react';
import api from '../../api/client';

export default function TicketingList() {
  const [tickets, setTickets] = useState([]);
  const [showNew, setShowNew] = useState(false);
  const [form, setForm] = useState({ department: 'education', subject: '', description: '' });

  const loadTickets = () => {
    api.get('/tickets').then(res => setTickets(res.data.data || []));
  };

  useEffect(() => {
    loadTickets();
  }, []);

  const createTicket = async () => {
    await api.post('/tickets', form);
    setShowNew(false);
    loadTickets();
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>تیکت‌ها</h2>
      <button onClick={() => setShowNew(true)}>تیکت جدید</button>

      {showNew && (
        <div style={{ marginTop: 16, padding: 16, background: '#f9f9f9' }}>
          <select value={form.department} onChange={e => setForm({...form, department: e.target.value})}>
            <option value="education">آموزش</option>
            <option value="technical">فنی</option>
            <option value="student_affairs">امور دانشجویی</option>
          </select>
          <input placeholder="موضوع" value={form.subject} onChange={e => setForm({...form, subject: e.target.value})} />
          <textarea placeholder="توضیحات" value={form.description} onChange={e => setForm({...form, description: e.target.value})} />
          <button onClick={createTicket}>ارسال</button>
        </div>
      )}

      <div style={{ marginTop: 24 }}>
        {tickets.map((t: any) => (
          <div key={t.id} style={{ padding: 12, border: '1px solid #ccc', marginBottom: 8 }}>
            <strong>{t.subject}</strong> — {t.status}
          </div>
        ))}
      </div>
    </div>
  );
}