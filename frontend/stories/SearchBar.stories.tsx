import type { Meta, StoryObj } from '@storybook/react';
import { TextField, InputAdornment } from '@mui/material';
import SearchIcon from '@mui/icons-material/Search';

const meta: Meta<any> = {
  title: 'Components/SearchBar',
  component: TextField,
  tags: ['autodocs'],
};

export default meta;

export const Default: StoryObj = {
  args: {
    placeholder: 'جستجو در درس‌ها...',
    fullWidth: true,
    InputProps: {
      startAdornment: (
        <InputAdornment position="start">
          <SearchIcon />
        </InputAdornment>
      ),
    },
  },
};