import React, { useState } from 'react';
import api from '../../api/client';

export default function BulkImport() {
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const handleImport = async () => {
    if (!file) return;
    setLoading(true);
    setMessage('');

    const formData = new FormData();
    formData.append('file', file);

    try {
      const res = await api.post('/owner/users/bulk-import', formData);
      setMessage(res.data.message || 'ایمپورت موفق');
    } catch (err: any) {
      setMessage('خطا در ایمپورت');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>ایمپورت انبوه کاربران</h2>
      <p>آپلود اکسل ۶۰۰ دانشجو → تولید خودکار پاکت رمز</p>
      
      <input type="file" onChange={e => setFile(e.target.files?.[0] || null)} />
      
      <button 
        onClick={handleImport} 
        disabled={!file || loading}
        style={{ marginTop: 16, padding: '10px 20px' }}
      >
        {loading ? 'در حال پردازش...' : 'شروع ایمپورت'}
      </button>

      {message && <p style={{ marginTop: 16, color: '#1976D2' }}>{message}</p>}
    </div>
  );
}