import type { Meta, StoryObj } from '@storybook/react';
import { Chip, Box, Typography } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/BroadcastThrottleStatus',
  component: Box,
};

export default meta;

export const CanSend: StoryObj = {
  render: () => <Chip label="می‌توانید پیام بفرستید" color="success" />,
};

export const RateLimited: StoryObj = {
  render: () => (
    <Box>
      <Chip label="محدود شده" color="error" />
      <Typography variant="caption" display="block">۸ دقیقه باقی‌مانده</Typography>
    </Box>
  ),
};