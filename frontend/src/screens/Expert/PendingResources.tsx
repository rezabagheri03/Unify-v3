import React, { useEffect, useState } from 'react';
import api from '../../api/client';

export default function PendingResources() {
  const [pending, setPending] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchPending = async () => {
    setLoading(true);
    try {
      const res = await api.get('/admin/resources/pending');
      setPending(res.data.data || []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPending();
  }, []);

  const approve = async (id: string) => {
    await api.post(`/admin/resources/${id}/approve`);
    fetchPending();
  };

  const reject = async (id: string) => {
    await api.post(`/admin/resources/${id}/reject`);
    fetchPending();
  };

  if (loading) return <div style={{ padding: 24 }}>در حال بارگذاری...</div>;

  return (
    <div style={{ padding: 24 }}>
      <h2>منابع در انتظار تأیید ({pending.length})</h2>

      {pending.length === 0 && <p>هیچ منبعی در انتظار تأیید نیست.</p>}

      {pending.map((res: any) => (
        <div key={res.id} style={{ 
          padding: 16, 
          border: '1px solid #ddd', 
          borderRadius: 8, 
          marginBottom: 12 
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div>
              <strong>{res.title}</strong>
              <div style={{ fontSize: 13, color: '#666' }}>
                توسط: {res.uploader?.first_name} {res.uploader?.last_name}
              </div>
            </div>
            <div>
              <button onClick={() => approve(res.id)} style={{ background: '#4caf50', color: 'white', marginRight: 8 }}>
                تأیید
              </button>
              <button onClick={() => reject(res.id)} style={{ background: '#f44336', color: 'white' }}>
                رد
              </button>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}