import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography, Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/AcademicCalendarEvent',
  component: Card,
};

export default meta;

export const Registration: StoryObj = {
  render: () => (
    <Card>
      <CardContent>
        <Typography variant="subtitle1">شروع انتخاب واحد</Typography>
        <Chip label="ثبت‌نام" color="primary" size="small" />
        <Typography variant="caption" display="block">۱۴۰۳/۰۷/۰۱</Typography>
      </CardContent>
    </Card>
  ),
};

export const ExamPeriod: StoryObj = {
  render: () => (
    <Card>
      <CardContent>
        <Typography variant="subtitle1">دوره امتحانات</Typography>
        <Chip label="امتحان" color="warning" size="small" />
        <Typography variant="caption" display="block">۱۴۰۳/۱۰/۱۵ - ۱۴۰۳/۱۰/۳۰</Typography>
      </CardContent>
    </Card>
  ),
};