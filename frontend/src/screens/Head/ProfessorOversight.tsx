import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Paper from '@mui/material/Paper';
import Alert from '@mui/material/Alert';
import api, { apiErrorMessage } from '../../api/client';

export default function ProfessorOversight() {
  const [rows, setRows] = useState<any[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    // Aggregate per-professor stats from the resources + specs endpoints.
    Promise.all([api.get('/specifications'), api.get('/resources')])
      .then(([s, r]) => {
        const specs = s.data?.data || [];
        const resources = r.data?.data || [];
        const profs = new Map<string, { name: string; specs: number; resources: number }>();
        specs.forEach((x: any) => {
          const id = x.professor_id;
          if (!id) return;
          const cur = profs.get(id) || { name: `${x.professor?.first_name || ''} ${x.professor?.last_name || id}`, specs: 0, resources: 0 };
          cur.specs += 1;
          profs.set(id, cur);
        });
        resources.forEach((x: any) => {
          const id = x.professor_id;
          if (!id) return;
          const cur = profs.get(id) || { name: id, specs: 0, resources: 0 };
          cur.resources += 1;
          profs.set(id, cur);
        });
        setRows([...profs.values()].sort((a, b) => b.resources - a.resources));
      })
      .catch((err) => setError(apiErrorMessage(err)));
  }, []);

  return (
    <Box>
      <Typography variant="h5" gutterBottom>نظارت بر اساتید</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <Paper>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>استاد</TableCell>
              <TableCell align="center">تعداد درس</TableCell>
              <TableCell align="center">منابع ثبت‌شده</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {rows.map((r) => (
              <TableRow key={r.name}>
                <TableCell>{r.name}</TableCell>
                <TableCell align="center">{r.specs}</TableCell>
                <TableCell align="center">{r.resources}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Paper>
      {rows.length === 0 && <Typography color="text.secondary" sx={{ mt: 2 }}>داده‌ای موجود نیست</Typography>}
    </Box>
  );
}
