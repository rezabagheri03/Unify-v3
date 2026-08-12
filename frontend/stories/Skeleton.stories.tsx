import type { Meta, StoryObj } from '@storybook/react';
import { Skeleton, Box } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/Skeleton',
  component: Skeleton,
};

export default meta;

export const CourseCard: StoryObj = {
  render: () => (
    <Box sx={{ width: 300 }}>
      <Skeleton variant="rectangular" height={140} />
      <Skeleton width="80%" height={30} sx={{ mt: 1 }} />
      <Skeleton width="60%" height={25} />
      <Skeleton width="40%" height={25} />
    </Box>
  ),
};