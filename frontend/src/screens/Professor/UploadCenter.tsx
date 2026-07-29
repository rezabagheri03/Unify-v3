import React, { useState } from 'react';
import api from '../../api/client';

export default function ProfessorUploadCenter() {
  const [file, setFile] = useState<File | null>(null);
  const [title, setTitle] = useState('');
  const [uploading, setUploading] = useState(false);

  const upload = async () => {
    if (!file || !title) return;
    setUploading(true);

    const formData = new FormData();
    formData.append('file', file);
    formData.append('title', title);
    formData.append('course_id', 'CS101');
    formData.append('professor_id', 'me');

    try {
      await api.post('/resources/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('منبع با موفقیت آپلود شد (تأیید خودکار)');
      setFile(null);
      setTitle('');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>مرکز آپلود استاد</h2>
      <p>منابع آپلود شده توسط استاد به صورت خودکار تأیید می‌شوند</p>

      <input 
        type="text" 
        placeholder="عنوان منبع" 
        value={title} 
        onChange={e => setTitle(e.target.value)}
        style={{ width: '100%', maxWidth: 500, padding: 10, marginBottom: 12 }}
      />
      
      <input type="file" onChange={e => setFile(e.target.files?.[0] || null)} />
      
      <button 
        onClick={upload} 
        disabled={!file || !title || uploading}
        style={{ marginTop: 16, padding: '10px 20px' }}
      >
        {uploading ? 'در حال آپلود...' : 'آپلود منبع'}
      </button>
    </div>
  );
}