import React, { useEffect, useState } from 'react';
import api from '../../api/client';

export default function ResourceHubList() {
  const [resources, setResources] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/resources').then(res => {
      setResources(res.data.data || []);
      setLoading(false);
    });
  }, []);

  return (
    <div style={{ padding: 20 }}>
      <h2>مرکز منابع (Evergreen)</h2>
      
      {loading && <p>در حال بارگذاری...</p>}
      
      <div style={{ display: 'grid', gap: 16 }}>
        {resources.map((res: any) => (
          <div key={res.id} style={{ border: '1px solid #ddd', padding: 16, borderRadius: 8 }}>
            <h4>{res.title}</h4>
            <p>{res.description}</p>
            <div style={{ fontSize: 12, color: '#666' }}>
              {res.badge_type && <span>✅ {res.badge_type}</span>} • 
              امتیاز: {res.average_rating || 0} ({res.rating_count})
            </div>
            <button onClick={() => window.open(`/api/resources/${res.id}/download`, '_blank')}>
              دانلود
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}