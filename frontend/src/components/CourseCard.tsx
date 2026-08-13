import React from 'react';
import { Link as RouterLink } from 'react-router-dom';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Chip from '@mui/material/Chip';
import Box from '@mui/material/Box';

export interface CourseCardSpec {
  id: string;
  course?: { name?: string; code?: string; credits?: number };
  professor?: { first_name?: string; last_name?: string };
  day_of_week?: string;
  time_start?: string;
  time_end?: string;
  is_next_day?: boolean;
  location?: string;
  exam_date_final_g?: string;
  shamsi_final?: string;
}

/**
 * P18 CourseCard — deterministic hash-colored header, day+time, location,
 * credits and exam date. Optionally links to the course's resources.
 */
export default function CourseCard({ spec, footerAction }: { spec: CourseCardSpec; footerAction?: React.ReactNode }) {
  const hue = (str?: string) => {
    if (!str) return 200;
    let h = 0;
    for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) % 360;
    return h;
  };
  const color = `hsl(${hue(spec.professor?.first_name || spec.course?.code)}, 55%, 42%)`;

  return (
    <Card sx={{ mb: 1, borderTop: `4px solid ${color}` }}>
      <CardContent>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Typography variant="subtitle1">
            {spec.course?.name}
            {spec.course?.code && <Chip size="small" label={spec.course.code} sx={{ ml: 1 }} />}
          </Typography>
          <Chip size="small" label={`${spec.course?.credits ?? '—'} واحد`} variant="outlined" />
        </Box>
        <Typography variant="body2" color="text.secondary">
          {spec.professor?.first_name} {spec.professor?.last_name}
        </Typography>
        <Typography variant="body2">
          {spec.day_of_week} {spec.time_start}–{spec.time_end}
          {spec.is_next_day ? ' (روز بعد)' : ''} • {spec.location}
        </Typography>
        {spec.shamsi_final && (
          <Typography variant="caption" color="text.secondary">امتحان: {spec.shamsi_final}</Typography>
        )}
        {footerAction && <Box sx={{ mt: 1 }}>{footerAction}</Box>}
      </CardContent>
    </Card>
  );
}

export function CourseCardLink({ spec, to }: { spec: CourseCardSpec; to: string }) {
  return (
    <Box component={RouterLink} to={to} sx={{ textDecoration: 'none', display: 'block' }}>
      <CourseCard spec={spec} />
    </Box>
  );
}
