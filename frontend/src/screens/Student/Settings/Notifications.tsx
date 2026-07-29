import React, { useEffect, useState } from 'react';
import api from '../../../api/client';
import { useNotificationsPolling } from '../../../api/polling';

export default function NotificationsSettings() {
  const notifications = useNotificationsPolling(30000);
  const [mutedSpecs, setMutedSpecs] = useState<any[]>([]);

  const toggleMute = async (specId: string, muted: boolean) => {
    await api.post('/notifications/mute', { specification_id: specId, muted });
  };

  return (
    <div style={{ padding: 24 }}>
      <h3>اعلان‌ها (Polling ۳۰ ثانیه + Cache ۵ ثانیه)</h3>
      
      <div style={{ marginTop: 20 }}>
        <h4>اعلان‌های جدید</h4>
        {notifications.length === 0 && <p>اعلان جدیدی وجود ندارد</p>}
        {notifications.map((n: any) => (
          <div key={n.id} style={{ padding: 12, background: '#e3f2fd', marginBottom: 8 }}>
            {n.title} — {n.body}
          </div>
        ))}
      </div>
    </div>
  );
}