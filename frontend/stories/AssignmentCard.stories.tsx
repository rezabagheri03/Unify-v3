import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography, Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/AssignmentCard',
  component: Card,
};

export default meta;

export const Pending: StoryObj = {
  render: () => (
    <Card>
      <CardContent>
        <Typography variant="subtitle1">تمرین ۳ - برنامه‌نویسی وب</Typography>
        <Chip label="در انتظار" color="warning" size="small" />
        <Typography variant="caption" display="block">مهلت: ۱۴۰۳/۰۵/۲۰</Typography>
      </CardContent>
    </Card>
  ),
};

export const Submitted: StoryObj = {
  render: () => (
    <Card>
      <CardContent>
        <Typography variant="subtitle1">پروژه نهایی</Typography>
        <Chip label="ارسال شده" color="success" size="small" />
      </CardContent>
    </Card>
  ),
};