import type { Meta, StoryObj } from '@storybook/react';
import { Box, Typography, Button } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/EmptyState',
  component: Box,
};

export default meta;

export const NoData: StoryObj = {
  render: () => (
    <Box textAlign="center" py={6}>
      <Typography variant="h6" gutterBottom>داده‌ای یافت نشد</Typography>
      <Button variant="outlined">بارگذاری مجدد</Button>
    </Box>
  ),
};