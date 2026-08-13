import React from 'react';
import Box from '@mui/material/Box';
import IconButton from '@mui/material/IconButton';
import StarIcon from '@mui/icons-material/Star';
import StarBorderIcon from '@mui/icons-material/StarBorder';

/** P18 RatingStars — 1..5 interactive or readonly stars. */
export default function RatingStars({ value, readonly, onChange }: {
  value: number;
  readonly?: boolean;
  onChange?: (v: number) => void;
}) {
  return (
    <Box sx={{ display: 'inline-flex', alignItems: 'center' }}>
      {[1, 2, 3, 4, 5].map((i) => (
        <IconButton
          key={i}
          size="small"
          disabled={readonly}
          onClick={() => onChange?.(i)}
          aria-label={`امتیاز ${i}`}
        >
          {i <= value ? <StarIcon sx={{ color: '#f5a623' }} fontSize="small" /> : <StarBorderIcon fontSize="small" />}
        </IconButton>
      ))}
    </Box>
  );
}
