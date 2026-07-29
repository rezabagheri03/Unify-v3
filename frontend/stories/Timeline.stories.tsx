import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/Timeline',
  component: Card,
};

export default meta;

export const AcademicCalendar: StoryObj = {
  render: () => (
    <Card>
      <CardContent>
        <Typography variant="h6" gutterBottom>تقویم تحصیلی ۱۴۰۳-۲</Typography>
        <ul style={{ paddingLeft: 20 }}>
          <li>شروع نیمسال: ۱۴۰۳/۰۷/۰۱</li>
          <li>مهلت حذف و اضافه: ۱۴۰۳/۰۷/۱۵</li>
          <li>امتحانات میان‌ترم: ۱۴۰۳/۰۹/۱۰</li>
          <li>امتحانات نهایی: ۱۴۰۳/۱۰/۱۵</li>
        </ul>
      </CardContent>
    </Card>
  ),
};