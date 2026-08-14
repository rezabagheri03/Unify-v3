import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/UserRoleBadge',
  component: Chip,
  tags: ['autodocs'],
};

export default meta;

export const Student: StoryObj = {
  args: { label: 'دانشجو', color: 'default' },
};

export const Professor: StoryObj = {
  args: { label: 'استاد', color: 'primary' },
};

export const Expert: StoryObj = {
  args: { label: 'کارشناس', color: 'info' },
};

export const Admin: StoryObj = {
  args: { label: 'ادمین', color: 'secondary' },
};

export const Owner: StoryObj = {
  args: { label: 'مالک', color: 'error' },
};