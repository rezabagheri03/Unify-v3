import type { Meta, StoryObj } from '@storybook/react';
import { TextField } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/ShamsiDatePicker',
  component: TextField,
};

export default meta;

export const Default: StoryObj = {
  render: () => (
    <TextField 
      label="تاریخ شمسی" 
      placeholder="1403/05/15"
      fullWidth 
    />
  ),
};