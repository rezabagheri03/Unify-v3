import React, { useState } from 'react';
import api from '../../api/client';

export default function BrandingLogo() {
  const [logo, setLogo] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [preview, setPreview] = useState<string | null>(null);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setLogo(file);
      setPreview(URL.createObjectURL(file));
    }
  };

  const uploadLogo = async () => {
    if (!logo) return;
    setUploading(true);

    const formData = new FormData();
    formData.append('logo', logo);

    try {
      await api.post('/admin/branding/logo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('لوگو با موفقیت آپلود شد');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>برندینگ و لوگو</h2>
      
      <div style={{ maxWidth: 400 }}>
        <input type="file" accept="image/*" onChange={handleFileChange} />
        
        {preview && (
          <div style={{ marginTop: 16 }}>
            <img src={preview} alt="Preview" style={{ maxWidth: '100%', maxHeight: 150 }} />
          </div>
        )}

        <button 
          onClick={uploadLogo} 
          disabled={!logo || uploading}
          style={{ marginTop: 16, padding: '10px 20px' }}
        >
          {uploading ? 'در حال آپلود...' : 'آپلود لوگو'}
        </button>
      </div>
    </div>
  );
}