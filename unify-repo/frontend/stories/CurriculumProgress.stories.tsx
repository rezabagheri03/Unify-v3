import type { Meta, StoryObj } from '@storybook/react';
import { Box, LinearProgress, Typography } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/CurriculumProgress',
  component: Box,
};

export default meta;

export const Progress: StoryObj = {
  render: () => (
    <Box>
      <Typography>پیشرفت درسی: ۸۷ واحد از ۱۴۰ واحد</Typography>
      <LinearProgress variant="determinate" value={62} sx={{ mt: 1, height: 8, borderRadius: 4 }} />
    </Box>
  ),
};