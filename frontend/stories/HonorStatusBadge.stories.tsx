import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/HonorStatusBadge',
  component: Chip,
  tags: ['autodocs'],
};

export default meta;

export const Normal: StoryObj = {
  args: {
    label: 'عادی',
    color: 'default',
  },
};

export const FinalSemester: StoryObj = {
  args: {
    label: 'ترم آخر',
    color: 'info',
  },
};

export const Conditional: StoryObj = {
  args: {
    label: 'مشروط',
    color: 'warning',
  },
};

export const GPA_A: StoryObj = {
  args: {
    label: 'معدل الف',
    color: 'success',
  },
};