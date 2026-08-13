import React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import Chip from '@mui/material/Chip';
import Box from '@mui/material/Box';
import IconButton from '@mui/material/IconButton';
import DownloadIcon from '@mui/icons-material/Download';
import StarIcon from '@mui/icons-material/Star';

export interface FileCardProps {
  id: string;
  title?: string;
  author?: string;
  average_rating?: number | null;
  rating_count?: number;
  download_count?: number;
  badge_type?: string | null;
  version?: number;
  mime?: string;
  onDownload?: (id: string) => void;
}

/** P18 FileCard — PDF/DOCX icon, author, Shamsi date, rating, downloads, badge. */
export default function FileCard(props: FileCardProps) {
  const isPdf = (props.mime || '').includes('pdf') || !props.mime;
  return (
    <Card sx={{ mb: 1 }}>
      <CardContent sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
          <Box
            sx={{
              width: 44, height: 56, borderRadius: 1, display: 'flex', alignItems: 'center',
              justifyContent: 'center', color: 'white', fontWeight: 700, fontSize: 12,
              bgcolor: isPdf ? '#e53935' : '#1976D2',
            }}
          >
            {isPdf ? 'PDF' : 'DOC'}
          </Box>
          <Box>
            <Typography variant="subtitle1">{props.title}</Typography>
            <Typography variant="body2" color="text.secondary">
              {props.author}
              {props.version ? ` • نسخه ${props.version}` : ''}
            </Typography>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mt: 0.5 }}>
              <Typography variant="caption" sx={{ display: 'flex', alignItems: 'center' }}>
                <StarIcon sx={{ fontSize: 16, color: '#f5a623' }} /> {props.average_rating ?? '—'} ({props.rating_count ?? 0})
              </Typography>
              <Typography variant="caption">دانلود: {props.download_count ?? 0}</Typography>
              {props.badge_type && (
                <Chip size="small" label={props.badge_type} color={props.badge_type === 'professor' ? 'secondary' : 'info'} />
              )}
            </Box>
          </Box>
        </Box>
        {props.onDownload && (
          <IconButton color="primary" onClick={() => props.onDownload?.(props.id)} aria-label="دانلود">
            <DownloadIcon />
          </IconButton>
        )}
      </CardContent>
    </Card>
  );
}
