import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/GradeChip',
  component: Chip,
  tags: ['autodocs'],
};

export default meta;

export const Passed: StoryObj = {
  args: { label: 'قبول', color: 'success' },
};

export const Failed: StoryObj = {
  args: { label: 'مردود', color: 'error' },
};

export const Conditional: StoryObj = {
  args: { label: 'مشروط', color: 'warning' },
};

export const FinalSemester: StoryObj = {
  args: { label: 'ترم آخر', color: 'info' },
};