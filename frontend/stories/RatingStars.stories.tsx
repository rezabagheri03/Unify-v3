import type { Meta, StoryObj } from '@storybook/react';
import { Rating, Box, Typography } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/RatingStars',
  component: Rating,
  tags: ['autodocs'],
};

export default meta;

export const ReadOnly: StoryObj = {
  args: {
    value: 4.5,
    readOnly: true,
  },
};

export const Interactive: StoryObj = {
  args: {
    defaultValue: 3,
  },
};

export const WithLabel: StoryObj = {
  render: () => (
    <Box>
      <Typography>امتیاز شما:</Typography>
      <Rating defaultValue={4} />
    </Box>
  ),
};