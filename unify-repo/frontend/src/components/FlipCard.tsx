import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import { useReducedMotion } from 'framer-motion';

/**
 * P18 FlipCard — exam-schedule flip card (F03/F05). Uses Framer Motion when
 * the user has no reduced-motion preference; falls back to a static card
 * otherwise (accessibility requirement from the docs).
 */
export default function FlipCard({
  front, back, flipped, onClick,
}: { front: React.ReactNode; back: React.ReactNode; flipped?: boolean; onClick?: () => void }) {
  const reduceMotion = useReducedMotion();

  if (reduceMotion) {
    // Reduced-motion fallback: show whichever side is currently active, no animation.
    return (
      <Card onClick={onClick} sx={{ cursor: onClick ? 'pointer' : 'default', mb: 1 }}>
        <CardContent>{flipped ? back : front}</CardContent>
      </Card>
    );
  }

  return (
    <Box
      onClick={onClick}
      sx={{
        perspective: 1200, cursor: onClick ? 'pointer' : 'default', mb: 1,
        transformStyle: 'preserve-3d',
      }}
    >
      <Box
        sx={{
          transition: 'transform 0.5s',
          transformStyle: 'preserve-3d',
          transform: flipped ? 'rotateY(180deg)' : 'rotateY(0deg)',
        }}
      >
        <Box sx={{ backfaceVisibility: 'hidden' }}>
          <Card><CardContent>{front}</CardContent></Card>
        </Box>
        <Box
          sx={{
            backfaceVisibility: 'hidden',
            transform: 'rotateY(180deg)',
            position: 'absolute', inset: 0,
          }}
        >
          <Card sx={{ height: '100%' }}><CardContent>{back}</CardContent></Card>
        </Box>
      </Box>
    </Box>
  );
}

export function ExamFlipCard({
  courseName, examDate, midtermDate, flipped, onClick,
}: { courseName?: string; examDate?: string; midtermDate?: string; flipped?: boolean; onClick?: () => void }) {
  return (
    <FlipCard
      flipped={flipped}
      onClick={onClick}
      front={
        <Box>
          <Typography variant="subtitle1">{courseName}</Typography>
          <Typography variant="caption" color="text.secondary">برای مشاهده جزئیات بزنید</Typography>
        </Box>
      }
      back={
        <Box>
          <Typography variant="subtitle1">{courseName}</Typography>
          <Typography variant="body2">امتحان نهایی: {examDate || '—'}</Typography>
          <Typography variant="body2">میان‌ترم: {midtermDate || '—'}</Typography>
        </Box>
      }
    />
  );
}
