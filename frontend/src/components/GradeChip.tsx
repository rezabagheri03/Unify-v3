import React from 'react';
import Chip from '@mui/material/Chip';

/** P18 GradeChip — grade display with pass/fail color (>=10 pass). */
export default function GradeChip({ grade }: { grade?: number | null }) {
  if (grade === null || grade === undefined) return <Chip size="small" label="—" variant="outlined" />;
  const pass = grade >= 10;
  return <Chip size="small" label={grade} color={pass ? 'success' : 'error'} />;
}
