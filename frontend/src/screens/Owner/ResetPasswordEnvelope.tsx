import React from 'react';
import api from '../../api/client';

export default function ResetPasswordEnvelope() {
  const generateEnvelopes = async () => {
    try {
      const res = await api.post('/owner/generate-envelopes', {}, { responseType: 'blob' });
      const url = URL.createObjectURL(res.data);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'envelopes.zip';
      a.click();
    } catch (err) {
      alert('خطا در تولید پاکت‌ها');
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>تولید پاکت رمز عبور</h2>
      <p>تولید ZIP حاوی پاکت‌های PDF با رمز موقت + QR برای همه کاربران</p>
      
      <button onClick={generateEnvelopes} style={{ marginTop: 16, padding: '12px 24px' }}>
        تولید پاکت‌ها (ZIP)
      </button>
    </div>
  );
}