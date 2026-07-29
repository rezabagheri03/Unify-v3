import type { Meta, StoryObj } from '@storybook/react';
import { Chip, Box } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/OfflineQueueRow',
  component: Box,
};

export default meta;

export const Pending: StoryObj = {
  render: () => <Chip label="در صف همگام‌سازی" color="warning" />,
};

export const Synced: StoryObj = {
  render: () => <Chip label="همگام‌سازی شد" color="success" />,
};

export const Failed: StoryObj = {
  render: () => <Chip label="ناموفق" color="error" />,
};