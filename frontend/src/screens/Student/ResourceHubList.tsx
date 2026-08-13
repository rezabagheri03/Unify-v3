import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import api, { apiErrorMessage } from '../../api/client';
import type { ResourceItem } from '../../api/types';
import RateStars from '../../components/RateStars';

export default function ResourceHubList() {
  const [resources, setResources] = useState<ResourceItem[]>([]);
  const [search, setSearch] = useState('');
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    try {
      const res = await api.get('/resources', { params: search ? { search } : {} });
      setResources((res.data?.data as ResourceItem[]) || []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search]);

  const download = async (id: string) => {
    try {
      // Open in new tab so the browser handles the file (Bearer token via header not possible
      // in plain <a>; use fetch + blob for authenticated download).
      const res = await api.get(`/resources/${id}/download`, { responseType: 'blob' });
      const url = URL.createObjectURL(res.data);
      const a = document.createElement('a');
      a.href = url;
      a.download = `resource-${id}.pdf`;
      a.click();
      URL.revokeObjectURL(url);
      setSnack('دانلود شروع شد');
    } catch (err: any) {
      setSnack(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>مرکز منابع (همیشه‌سبز)</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <TextField fullWidth label="جستجوی منبع" value={search} onChange={(e) => setSearch(e.target.value)} sx={{ mb: 2 }} />
      {resources.map((r) => (
        <Card key={r.id} sx={{ mb: 1 }}>
          <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Box>
              <Typography variant="subtitle1">
                {r.title}
                {r.badge_type && (
                  <Chip size="small" label={r.badge_type} color={r.badge_type === 'professor' ? 'secondary' : 'info'} sx={{ ml: 1 }} />
                )}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {r.course?.name} • {r.professor?.first_name} {r.professor?.last_name} • دانلود {r.download_count ?? 0}
              </Typography>
              <RateStars
                resourceId={r.id}
                average={r.average_rating}
                count={r.rating_count}
                onMessage={setSnack}
                onRated={(avg) =>
                  setResources((prev) =>
                    prev.map((x) => (x.id === r.id ? { ...x, average_rating: avg } : x))
                  )
                }
              />
            </Box>
            <Box>
              <Button size="small" variant="contained" onClick={() => download(r.id)}>دانلود</Button>
            </Box>
          </CardContent>
        </Card>
      ))}
      {resources.length === 0 && <Typography color="text.secondary">منبعی یافت نشد</Typography>}
      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}
