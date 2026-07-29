import React, { useEffect, useState } from 'react';
import api from '../../api/client';

export default function AuditLogsViewer() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/owner/audit-logs').then(res => {
      setLogs(res.data.data || []);
      setLoading(false);
    });
  }, []);

  if (loading) return <div style={{ padding: 24 }}>در حال بارگذاری لاگ‌ها...</div>;

  return (
    <div style={{ padding: 24 }}>
      <h2>لاگ‌های Audit ({logs.length})</h2>
      
      <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: 16 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>کاربر</th>
            <th style={{ padding: 12, textAlign: 'right' }}>عملیات</th>
            <th style={{ padding: 12, textAlign: 'right' }}>منبع</th>
            <th style={{ padding: 12, textAlign: 'right' }}>زمان</th>
          </tr>
        </thead>
        <tbody>
          {logs.map((log: any) => (
            <tr key={log.id} style={{ borderBottom: '1px solid #eee' }}>
              <td style={{ padding: 12 }}>{log.user_id}</td>
              <td style={{ padding: 12 }}>{log.action}</td>
              <td style={{ padding: 12 }}>{log.resource_type}</td>
              <td style={{ padding: 12 }}>{new Date(log.timestamp).toLocaleString('fa-IR')}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}