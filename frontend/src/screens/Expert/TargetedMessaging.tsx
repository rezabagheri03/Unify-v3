import React, { useState } from 'react';
import api from '../../api/client';

export default function TargetedMessaging() {
  const [form, setForm] = useState({ recipients: '', subject: '', body: '' });
  const [loading, setLoading] = useState(false);

  const send = async () => {
    if (!form.body) return;
    setLoading(true);
    try {
      await api.post('/messages/send', {
        recipient_ids: form.recipients.split(',').map(s => s.trim()),
        subject: form.subject,
        body: form.body
      });
      alert('پیام ارسال شد');
      setForm({ recipients: '', subject: '', body: '' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>پیام‌رسانی هدفمند</h2>
      <input placeholder="شناسه‌ها (جدا با کاما)" value={form.recipients} onChange={e => setForm({ ...form, recipients: e.target.value })} style={{ width: '100%', maxWidth: 500, padding: 8, marginBottom: 8 }} />
      <input placeholder="موضوع" value={form.subject} onChange={e => setForm({ ...form, subject: e.target.value })} style={{ width: '100%', maxWidth: 500, padding: 8, marginBottom: 8 }} />
      <textarea placeholder="متن پیام" value={form.body} onChange={e => setForm({ ...form, body: e.target.value })} rows={5} style={{ width: '100%', maxWidth: 500, padding: 8 }} />
      <button onClick={send} disabled={loading} style={{ marginTop: 12 }}>ارسال پیام</button>
    </div>
  );
}