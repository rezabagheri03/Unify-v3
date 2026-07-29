import React, { useState } from 'react';
import api from '../../api/client';

export default function ImportExcel() {
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [message, setMessage] = useState('');

  const handleImport = async () => {
    if (!file) return;
    setUploading(true);
    setMessage('');

    const formData = new FormData();
    formData.append('file', file);

    try {
      const res = await api.post('/excel/import/users', formData);
      setMessage(res.data.message || 'ایمپورت با موفقیت انجام شد');
    } catch (err: any) {
      if (err.response?.data instanceof Blob) {
        const url = URL.createObjectURL(err.response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'error-report.xlsx';
        a.click();
        setMessage('خطا در ایمپورت — گزارش اکسل دانلود شد');
      } else {
        setMessage('خطا در ایمپورت');
      }
    } finally {
      setUploading(false);
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>ایمپورت اکسل</h2>
      <p>در صورت وجود خطا، گزارش اکسل با ستون قرمز «خطا» دانلود می‌شود</p>

      <input type="file" onChange={e => setFile(e.target.files?.[0] || null)} />
      
      <button 
        onClick={handleImport} 
        disabled={!file || uploading}
        style={{ marginTop: 16, padding: '10px 20px' }}
      >
        {uploading ? 'در حال ایمپورت...' : 'شروع ایمپورت'}
      </button>

      {message && <p style={{ marginTop: 16, color: '#1976D2' }}>{message}</p>}
    </div>
  );
}