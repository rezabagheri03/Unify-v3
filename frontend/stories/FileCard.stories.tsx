import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography, Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/FileCard',
  component: Card,
};

export default meta;

export const PDF: StoryObj = {
  render: () => (
    <Card sx={{ maxWidth: 280 }}>
      <CardContent>
        <Typography>جزوه برنامه‌نویسی.pdf</Typography>
        <Chip label="PDF • 2.4 MB" size="small" />
        <Typography variant="caption" display="block">امتیاز: 4.5 (۱۲ رای) • ۳۴۵ دانلود</Typography>
      </CardContent>
    </Card>
  ),
};

export const ProfessorBadge: StoryObj = {
  render: () => (
    <Card sx={{ maxWidth: 280, border: '1px solid #4caf50' }}>
      <CardContent>
        <Typography>اسلایدهای درس.pptx</Typography>
        <Chip label="استاد" color="success" size="small" />
      </CardContent>
    </Card>
  ),
};