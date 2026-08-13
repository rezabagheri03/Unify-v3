import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Checkbox from '@mui/material/Checkbox';
import LinearProgress from '@mui/material/LinearProgress';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function CurriculumCharts() {
  const [charts, setCharts] = useState<any[]>([]);
  const [passed, setPassed] = useState<Record<string, boolean>>({});
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/curriculum')
      .then((res) => {
        const list = Array.isArray(res.data) ? res.data : [];
        setCharts(list);
        const chart = list[0];
        if (chart) {
          // Seed passed map from chart data if present
          const map: Record<string, boolean> = {};
          const data = chart.chart_data || {};
          (data.passed_courses || []).forEach((c: string) => { map[c] = true; });
          setPassed(map);
        }
      })
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  const toggle = async (courseCode: string, checked: boolean) => {
    setPassed((p) => ({ ...p, [courseCode]: checked }));
    // OR-merge offline-safe via /offline/sync (F19)
    try {
      await api.post('/offline/sync', {
        items: [{
          type: 'curriculum_checkbox',
          payload: { course_id: courseCode, entry_year: 1401, passed: checked },
          idempotency_key: crypto.randomUUID(),
        }],
      });
    } catch {
      // will retry via background sync
    }
  };

  const courses = charts[0]?.chart_data?.courses || ['CS101', 'CS102', 'CS103', 'CS104'];
  const passedCount = courses.filter((c: string) => passed[c]).length;
  const progress = courses.length ? (passedCount / courses.length) * 100 : 0;

  return (
    <Box>
      <Typography variant="h5" gutterBottom>نمودار درسی</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Card sx={{ mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2">پیشرفت: {passedCount}/{courses.length} درس</Typography>
          <LinearProgress variant="determinate" value={progress} sx={{ mt: 1 }} />
        </CardContent>
      </Card>
      {courses.map((code: string) => (
        <Card key={code} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', alignItems: 'center' }}>
            <Checkbox checked={!!passed[code]} onChange={(e) => toggle(code, e.target.checked)} />
            <Typography>{code}</Typography>
          </CardContent>
        </Card>
      ))}
    </Box>
  );
}
