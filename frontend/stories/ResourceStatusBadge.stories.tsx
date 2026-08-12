import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/ResourceStatusBadge',
  component: Chip,
};

export default meta;

export const Pending: StoryObj = {
  args: {
    label: 'در انتظار تأیید',
    color: 'warning',
  },
};

export const Approved: StoryObj = {
  args: {
    label: 'تأیید شده',
    color: 'success',
  },
};

export const Rejected: StoryObj = {
  args: {
    label: 'رد شده',
    color: 'error',
  },
};

export const ProfessorBadge: StoryObj = {
  args: {
    label: 'استاد',
    color: 'primary',
  },
};