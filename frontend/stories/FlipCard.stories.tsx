import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography } from '@mui/material';
import { motion } from 'framer-motion';

const meta: Meta<any> = {
  title: 'Components/FlipCard',
  component: Card,
};

export default meta;

export const ExamSchedule: StoryObj = {
  render: () => (
    <motion.div whileHover={{ rotateY: 180 }} style={{ width: 260, height: 160, perspective: 1000 }}>
      <Card sx={{ width: '100%', height: '100%' }}>
        <CardContent>
          <Typography variant="h6">امتحان نهایی</Typography>
          <Typography>برنامه‌نویسی وب</Typography>
          <Typography variant="caption">۱۴۰۳/۱۰/۱۵ - ۹ صبح</Typography>
        </CardContent>
      </Card>
    </motion.div>
  ),
};