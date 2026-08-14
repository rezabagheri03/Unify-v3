import React, { useMemo, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import TextField from '@mui/material/TextField';
import CircularProgress from '@mui/material/CircularProgress';
import FormGroup from '@mui/material/FormGroup';
import FormControlLabel from '@mui/material/FormControlLabel';
import Checkbox from '@mui/material/Checkbox';
import api, { apiErrorMessage } from '../api/client';

/**
 * Golden scheduler UI (TODO-027 / F04): preference inputs + top-15 suggested
 * schedules from GET /golden-schedule. Suggestions are read-only — the student
 * still adds courses one by one so honor/prereq warnings stay visible.
 */
export default function GoldenSuggest({ specs }: { specs: any[] }) {
  const [maxGap, setMaxGap] = useState('');
  const [preferred, setPreferred] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [suggestions, setSuggestions] = useState<any[] | null>(null);
  const [fromCache, setFromCache] = useState(false);

  const professors = useMemo(() => {
    const map = new Map<string, string>();
    for (const s of specs) {
      if (s.professor?.id) {
        map.set(s.professor.id, `${s.professor.first_name ?? ''} ${s.professor.last_name ?? ''}`.trim());
      }
    }
    return [...map.entries()];
  }, [specs]);

  const toggleProf = (id: string) =>
    setPreferred((p) => (p.includes(id) ? p.filter((x) => x !== id) : [...p, id]));

  const generate = async () => {
    setLoading(true);
    setError('');
    try {
      const params: any = {};
      if (maxGap !== '') params.maxGap = Number(maxGap);
      if (preferred.length > 0) params.preferProfessors = preferred;
      const res = await api.get('/golden-schedule', { params });
      setSuggestions(res.data?.suggestions || []);
      setFromCache(Boolean(res.data?.cached));
    } catch (err: any) {
      setError(apiErrorMessage(err));
      setSuggestions(null);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Card sx={{ mb: 2 }}>
      <CardContent>
        <Typography variant="h6" gutterBottom>چینش طلایی (پیشنهاد خودکار برنامه)</Typography>
        <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', flexWrap: 'wrap', mb: 1 }}>
          <TextField
            size="small" type="number" label="حداکثر فاصله خالی (ساعت)"
            value={maxGap} onChange={(e) => setMaxGap(e.target.value)}
            inputProps={{ min: 0, max: 8, step: 0.5 }} sx={{ width: 220 }}
          />
          <Button variant="contained" onClick={generate} disabled={loading}>
            {loading ? <CircularProgress size={20} /> : 'دریافت پیشنهاد'}
          </Button>
          {fromCache && <Chip size="small" label="از حافظه موقت" />}
        </Box>
        {professors.length > 0 && (
          <FormGroup row>
            {professors.map(([id, name]) => (
              <FormControlLabel
                key={id}
                control={<Checkbox size="small" checked={preferred.includes(id)} onChange={() => toggleProf(id)} />}
                label={name}
              />
            ))}
          </FormGroup>
        )}
        {error && <Alert severity="error" sx={{ mt: 1 }}>{error}</Alert>}
        {suggestions && suggestions.length === 0 && (
          <Alert severity="info" sx={{ mt: 1 }}>چینش سازگاری یافت نشد؛ ترجیحات را تغییر دهید.</Alert>
        )}
        {suggestions?.map((sug: any, i: number) => (
          <Card key={i} variant="outlined" sx={{ mt: 1.5 }}>
            <CardContent>
              <Box sx={{ display: 'flex', gap: 1, alignItems: 'center', flexWrap: 'wrap' }}>
                <Chip size="small" color="primary" label={`رتبه ${i + 1} — امتیاز ${sug.score}`} />
                <Chip size="small" label={`${sug.credits} واحد`} />
                <Typography variant="body2" color="text.secondary">{sug.explanation}</Typography>
              </Box>
              {(sug.specs || []).map((sp: any) => (
                <Typography key={sp.id} variant="body2" sx={{ mt: 0.5 }}>
                  • {sp.course?.name ?? sp.course_id} — {sp.day_of_week} {sp.time_start} تا {sp.time_end}
                </Typography>
              ))}
            </CardContent>
          </Card>
        ))}
      </CardContent>
    </Card>
  );
}
