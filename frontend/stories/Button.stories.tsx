import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '@mui/material';

const meta: Meta<typeof Button> = {
  title: 'Components/Button',
  component: Button,
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Primary: Story = {
  args: {
    variant: 'contained',
    children: 'دکمه اصلی',
  },
};

export const Outlined: Story = {
  args: {
    variant: 'outlined',
    children: 'دکمه خط‌دار',
  },
};