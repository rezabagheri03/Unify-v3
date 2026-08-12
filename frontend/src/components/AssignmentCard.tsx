import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import StatusBadge from './StatusBadge';
import GradeChip from './GradeChip';

/** P18 AssignmentCard — tracker row with status + grade. */
export default function AssignmentCard({ assignment }: { assignment: any }) {
  const a = assignment || {};
  return (
    <Card sx={{ mb: 1 }}>
      <CardContent>
        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
          <Typography variant="subtitle1">{a.title}</Typography>
          <Box sx={{ display: 'flex', gap: 1 }}>
            <StatusBadge status={a.status} />
            <GradeChip grade={a.grade} />
          </Box>
        </Box>
        <Typography variant="body2" color="text.secondary">
          مهلت: {a.shamsi_original || (a.due_date_g ? new Date(a.due_date_g).toLocaleDateString('fa-IR') : '—')}
        </Typography>
      </CardContent>
    </Card>
  );
}
