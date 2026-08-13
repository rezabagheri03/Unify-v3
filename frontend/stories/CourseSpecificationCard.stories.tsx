import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography, Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/CourseSpecificationCard',
  component: Card,
  tags: ['autodocs'],
};

export default meta;

export const Default: StoryObj = {
  render: () => (
    <Card sx={{ maxWidth: 340 }}>
      <CardContent>
        <Typography variant="h6">برنامه‌نویسی وب</Typography>
        <Typography color="text.secondary">دکتر رضایی</Typography>
        <Chip label="شنبه ۸-۱۰" size="small" sx={{ mt: 1 }} />
        <Typography variant="body2" sx={{ mt: 1 }}>دانشکده کامپیوتر • ۳ واحد</Typography>
      </CardContent>
    </Card>
  ),
};

export const WithExamDate: StoryObj = {
  render: () => (
    <Card sx={{ maxWidth: 340, border: '1px solid #1976D2' }}>
      <CardContent>
        <Typography variant="h6">هوش مصنوعی</Typography>
        <Typography>دکتر کریمی — دوشنبه ۱۰-۱۲</Typography>
        <Chip label="امتحان: ۱۴۰۳/۱۰/۱۵" color="warning" size="small" sx={{ mt: 1 }} />
      </CardContent>
    </Card>
  ),
};