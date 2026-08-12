import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/TicketStatusChip',
  component: Chip,
};

export default meta;

export const Open: StoryObj = {
  args: {
    label: 'باز',
    color: 'warning',
  },
};

export const InProgress: StoryObj = {
  args: {
    label: 'در حال بررسی',
    color: 'info',
  },
};

export const Answered: StoryObj = {
  args: {
    label: 'پاسخ داده شده',
    color: 'success',
  },
};

export const Closed: StoryObj = {
  args: {
    label: 'بسته شده',
    color: 'default',
  },
};