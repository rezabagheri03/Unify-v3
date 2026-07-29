import type { Meta, StoryObj } from '@storybook/react';
import { Chip, Box, Typography } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/TicketRow',
  component: Box,
};

export default meta;

export const Open: StoryObj = {
  render: () => (
    <Box display="flex" alignItems="center" gap={2}>
      <Typography>مشکل در انتخاب واحد</Typography>
      <Chip label="باز" color="warning" size="small" />
    </Box>
  ),
};

export const Escalated: StoryObj = {
  render: () => (
    <Box display="flex" alignItems="center" gap={2}>
      <Typography>عدم دسترسی به منابع</Typography>
      <Chip label="Escalate شده (سطح ۲)" color="error" size="small" />
    </Box>
  ),
};