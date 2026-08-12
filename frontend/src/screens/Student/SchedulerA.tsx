import React, { useEffect, useState } from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Chip from '@mui/material/Chip';
import Alert from '@mui/material/Alert';
import Snackbar from '@mui/material/Snackbar';
import Radio from '@mui/material/Radio';
import RadioGroup from '@mui/material/RadioGroup';
import FormControlLabel from '@mui/material/FormControlLabel';
import FormControl from '@mui/material/FormControl';
import FormLabel from '@mui/material/FormLabel';
import Checkbox from '@mui/material/Checkbox';
import api, { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../stores/authStore';
import GoldenSuggest from '../../components/GoldenSuggest';

const STATUS_LABELS: Record<string, string> = {
  normal: 'عادی (۱۲ تا ۲۰ واحد)',
  conditional: 'مشروط (حداکثر ۱۴ واحد)',
  gpa_a: 'معدل الف (حداکثر ۲۴ واحد)',
  final_semester: 'ترم آخر (حداکثر ۲۴ واحد + نادیده‌گرفتن تداخل)',
};

export default function SchedulerA() {
  const { user, updateUser } = useAuthStore();
  const [specs, setSpecs] = useState<any[]>([]);
  const [temp, setTemp] = useState<any[]>([]);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState(user?.academic_status_declared || '');
  const [acknowledged, setAcknowledged] = useState(Boolean(user?.is_honor_system_acknowledged));
  const [snack, setSnack] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    try {
      const [s, t] = await Promise.all([
        api.get('/specifications', { params: search ? { search } : {} }),
        api.get('/enrollment/temp'),
      ]);
      setSpecs(s.data?.data || []);
      setTemp(Array.isArray(t.data) ? t.data : []);
    } catch (err: any) {
      setError(apiErrorMessage(err));
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search]);

  const declareHonor = async () => {
    try {
      await api.post('/users/me/academic-status', { status, acknowledged: true });
      updateUser({ academic_status_declared: status, is_honor_system_acknowledged: true });
      setSnack('وضعیت تحصیلی ثبت شد');
    } catch (err: any) {
      setSnack(apiErrorMessage(err));
    }
  };

  const addTemp = async (specId: string) => {
    try {
      const res = await api.post('/enrollment/temp', { specification_id: specId });
      const warnings = res.data?.warnings || [];
      setSnack(res.data?.message || 'به لیست موقت اضافه شد');
      if (warnings.length > 0) {
        setError(warnings.map((w: any) => w.message).join(' • '));
      }
      load();
    } catch (err: any) {
      setSnack(apiErrorMessage(err, 'خطا'));
    }
  };

  const removeTemp = async (id: string) => {
    try {
      await api.delete(`/enrollment/temp/${id}`);
      setSnack('از لیست موقت حذف شد');
      load();
    } catch (err: any) {
      setSnack(apiErrorMessage(err));
    }
  };

  const finalize = async () => {
    try {
      const res = await api.post('/enrollment/final');
      setSnack(res.data?.message || 'نهایی شد');
      load();
    } catch (err: any) {
      setSnack(apiErrorMessage(err));
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>فاز A — جستجو و انتخاب موقت</Typography>

      {/* Honor declaration (F02) */}
      <Card sx={{ mb: 2 }}>
        <CardContent>
          <FormControl component="fieldset">
            <FormLabel component="legend">وضعیت تحصیلی (سیستم افتخار — خوداظهاری)</FormLabel>
            <RadioGroup row value={status} onChange={(e) => setStatus(e.target.value)}>
              {Object.entries(STATUS_LABELS).map(([k, v]) => (
                <FormControlLabel key={k} value={k} control={<Radio />} label={v} />
              ))}
            </RadioGroup>
          </FormControl>
          <FormControlLabel
            control={<Checkbox checked={acknowledged} onChange={(e) => setAcknowledged(e.target.checked)} />}
            label="صحت اطلاعات را تأیید می‌کنم؛ مسئولیت با من است"
          />
          <Button variant="outlined" onClick={declareHonor} disabled={!status || !acknowledged} sx={{ ml: 2 }}>
            ثبت وضعیت
          </Button>
        </CardContent>
      </Card>

      {error && <Alert severity="warning" sx={{ mb: 2 }} onClose={() => setError('')}>{error}</Alert>}

      {/* Golden-schedule suggestions (F04 / TODO-027) */}
      <GoldenSuggest specs={specs} />

      <TextField
        fullWidth label="جستجوی درس (نام یا کد)" value={search}
        onChange={(e) => setSearch(e.target.value)} sx={{ mb: 2 }}
      />

      <GridRow title={`مشخصات موجود (${specs.length})`}>
        {specs.map((spec: any) => (
          <Card key={spec.id} sx={{ mb: 1 }}>
            <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <Box>
                <Typography variant="subtitle1">
                  {spec.course?.name} <Chip size="small" label={spec.course?.code} />
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {spec.professor?.first_name} {spec.professor?.last_name} • {spec.day_of_week} {spec.time_start}–{spec.time_end} • {spec.location}
                </Typography>
              </Box>
              <Button size="small" variant="contained" onClick={() => addTemp(spec.id)}>افزودن</Button>
            </CardContent>
          </Card>
        ))}
        {specs.length === 0 && <Typography color="text.secondary">درسی یافت نشد</Typography>}
      </GridRow>

      <GridRow title={`لیست موقت (${temp.length})`}>
        {temp.map((enr: any) => (
          <Card key={enr.id} sx={{ mb: 1 }}>
            <CardContent sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <Box>
                <Typography variant="subtitle1">{enr.specification?.course?.name}</Typography>
                <Typography variant="body2" color="text.secondary">
                  {enr.specification?.day_of_week} {enr.specification?.time_start}–{enr.specification?.time_end}
                </Typography>
              </Box>
              <Button size="small" color="error" variant="outlined" onClick={() => removeTemp(enr.id)}>حذف</Button>
            </CardContent>
          </Card>
        ))}
        {temp.length === 0 && <Typography color="text.secondary">لیست موقت خالی است</Typography>}
        {temp.length > 0 && (
          <Button variant="contained" color="success" sx={{ mt: 1 }} onClick={finalize}>
            نهایی‌سازی انتخاب واحد
          </Button>
        )}
      </GridRow>

      <Snackbar open={!!snack} autoHideDuration={4000} onClose={() => setSnack('')} message={snack} />
    </Box>
  );
}

function GridRow({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <Box sx={{ mb: 3 }}>
      <Typography variant="h6" gutterBottom>{title}</Typography>
      {children}
    </Box>
  );
}
