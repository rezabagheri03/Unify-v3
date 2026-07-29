import React, { useEffect, useState } from 'react';
import api from '../../api/client';

export default function AssignmentTrackerList() {
  const [assignments, setAssignments] = useState([]);

  useEffect(() => {
    api.get('/assignments').then(res => setAssignments(res.data || []));
  }, []);

  return (
    <div style={{ padding: 24 }}>
      <h2>ردیاب تکالیف</h2>
      {assignments.map((a: any) => (
        <div key={a.id} style={{ padding: 12, border: '1px solid #ddd', marginBottom: 8 }}>
          {a.title} — {a.due_date_g} — <strong>{a.status}</strong>
        </div>
      ))}
    </div>
  );
}