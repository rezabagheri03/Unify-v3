import React, { useEffect, useState } from 'react';
import { SyncQueue } from '../../../db/idb';
import { syncOfflineQueue } from '../../../utils/offlineSync';

export default function OfflineQueue() {
  const [queue, setQueue] = useState<any[]>([]);

  const loadQueue = async () => {
    const items = await SyncQueue.getAll();
    setQueue(items);
  };

  useEffect(() => {
    loadQueue();
  }, []);

  const handleSync = async () => {
    await syncOfflineQueue();
    await loadQueue();
  };

  return (
    <div style={{ padding: 20 }}>
      <h3>صف صف همگام‌سازی آفلاین</h3>
      <button onClick={handleSync}>همگام‌سازی دستی</button>

      <table style={{ width: '100%', marginTop: 16 }}>
        <thead>
          <tr>
            <th>نوع</th>
            <th>وضعیت</th>
            <th>زمان</th>
          </tr>
        </thead>
        <tbody>
          {queue.map((item, index) => (
            <tr key={index}>
              <td>{item.type}</td>
              <td>{item.status}</td>
              <td>{new Date(item.created_at).toLocaleTimeString('fa-IR')}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}