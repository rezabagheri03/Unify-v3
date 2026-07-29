import React, { useState, useEffect } from 'react';
import api from '../../api/client';

export default function SchedulerA() {
  const [specs, setSpecs] = useState([]);
  const [tempEnrollments, setTempEnrollments] = useState([]);
  const [search, setSearch] = useState('');

  useEffect(() => {
    api.get('/specifications').then(res => setSpecs(res.data.data || []));
    api.get('/enrollment/temp').then(res => setTempEnrollments(res.data || []));
  }, []);

  const addToTemp = async (specId: string) => {
    try {
      await api.post('/enrollment/temp', { specification_id: specId });
      alert('به لیست موقت اضافه شد');
      // Refresh temp list
      const res = await api.get('/enrollment/temp');
      setTempEnrollments(res.data);
    } catch (err: any) {
      alert(err.response?.data?.message || 'خطا');
    }
  };

  const finalize = async () => {
    try {
      await api.post('/enrollment/final');
      alert('انتخاب واحد نهایی شد');
    } catch (err: any) {
      alert(err.response?.data?.message);
    }
  };

  return (
    <div style={{ padding: 20 }}>
      <h2>فاز A - جستجو و انتخاب موقت</h2>
      
      <input 
        placeholder="جستجو..." 
        value={search} 
        onChange={e => setSearch(e.target.value)}
        style={{ width: '100%', padding: 8, marginBottom: 16 }}
      />

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
        {/* Available Specs */}
        <div>
          <h3>مشخصات موجود</h3>
          {specs.filter((s: any) => 
            s.course_name?.toLowerCase().includes(search.toLowerCase()) ||
            s.course?.name?.toLowerCase().includes(search.toLowerCase())
          ).map((spec: any) => (
            <div key={spec.id} style={{ border: '1px solid #ccc', padding: 12, marginBottom: 8 }}>
              <strong>{spec.course?.name}</strong> - {spec.professor?.first_name} {spec.professor?.last_name}<br />
              {spec.day_of_week} {spec.time_start}-{spec.time_end} • {spec.location}
              <button onClick={() => addToTemp(spec.id)} style={{ marginLeft: 10 }}>
                اضافه به موقت
              </button>
            </div>
          ))}
        </div>

        {/* Temporary List */}
        <div>
          <h3>لیست موقت ({tempEnrollments.length})</h3>
          {tempEnrollments.map((enr: any) => (
            <div key={enr.id} style={{ padding: 8, background: '#f5f5f5', marginBottom: 6 }}>
              {enr.specification?.course?.name}
            </div>
          ))}
          
          {tempEnrollments.length > 0 && (
            <button onClick={finalize} style={{ marginTop: 16, background: '#1976D2', color: 'white', padding: 12 }}>
              نهایی کردن انتخاب واحد
            </button>
          )}
        </div>
      </div>
    </div>
  );
}