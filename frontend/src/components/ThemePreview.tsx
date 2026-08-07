import React from 'react';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';

const THEMES = [
  { name: 'Unify Blue', primary: '#1976D2' },
  { name: 'Emerald', primary: '#00897b' },
  { name: 'Sunset', primary: '#e65100' },
  { name: 'Violet', primary: '#6a1b9a' },
  { name: 'Slate', primary: '#37474f' },
];

/** P18 ThemePreview — branding preset selector (F18). */
export default function ThemePreview({ active, onSelect }: { active: string; onSelect: (name: string) => void }) {
  return (
    <Box sx={{ display: 'flex', gap: 1.5, flexWrap: 'wrap' }}>
      {THEMES.map((t) => (
        <Card
          key={t.name}
          onClick={() => onSelect(t.name)}
          sx={{
            cursor: 'pointer', minWidth: 140,
            outline: active === t.name ? `3px solid ${t.primary}` : 'none',
          }}
        >
          <CardContent>
            <Box sx={{ height: 28, borderRadius: 1, bgcolor: t.primary, mb: 1 }} />
            <Typography variant="caption">{t.name}</Typography>
          </CardContent>
        </Card>
      ))}
    </Box>
  );
}
