import React, { useEffect, useState } from 'react';
import api from '../../api/client';

export default function InboxList() {
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/messages').then(res => {
      setMessages(res.data.data || []);
      setLoading(false);
    });
  }, []);

  return (
    <div style={{ padding: 20 }}>
      <h2>صندوق پیام‌ها (Unified Inbox)</h2>
      
      {loading ? <p>در حال بارگذاری...</p> : (
        messages.length === 0 ? (
          <p>پیامی وجود ندارد</p>
        ) : (
          messages.map((msg: any) => (
            <div key={msg.id} style={{ padding: 16, border: '1px solid #ddd', marginBottom: 12 }}>
              <strong>{msg.subject}</strong>
              <p>{msg.body?.substring(0, 100)}...</p>
              <small>{new Date(msg.sent_at).toLocaleDateString('fa-IR')}</small>
            </div>
          ))
        )
      )}
    </div>
  );
}