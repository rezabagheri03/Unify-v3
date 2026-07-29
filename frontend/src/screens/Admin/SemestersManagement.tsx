import React, { useState } from 'react';
import api from '../../api/client';

export default function SemestersManagement() {
  const [loading, setLoading] = useState(false);

  const createNewSemester = async () => {
    setLoading(true);
    try {
      await api.post('/admin/semesters', {
        name: '1403-2',
        start_shamsi: '1403/07/01',
        end_shamsi: '1404/01/31',
        is_current: true
      });
      alert('نیمسال جدید با موفقیت ایجاد شد');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت نیمسال‌ها</h2>
      
      <div style={{ background: '#f5f5f5', padding: 20, borderRadius: 8, maxWidth: 500 }}>
        <h4>ایجاد نیمسال جدید</h4>
        <button 
          onClick={createNewSemester} 
          disabled={loading}
          style={{ padding: '12px 24px', background: '#1976D2', color: 'white', marginTop: 12 }}
        >
          {loading ? 'در حال ایجاد...' : 'ایجاد نیمسال جدید'}
        </button>
      </div>
    </div>
  );
}