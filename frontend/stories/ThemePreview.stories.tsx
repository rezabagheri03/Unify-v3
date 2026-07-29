import type { Meta, StoryObj } from '@storybook/react';
import { Card, CardContent, Typography, Button, Switch } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/ThemePreview',
  component: Card,
};

export default meta;

export const LightMode: StoryObj = {
  render: () => (
    <Card>
      <CardContent>
        <Typography>تم روشن</Typography>
        <Button variant="contained">دکمه</Button>
      </CardContent>
    </Card>
  ),
};

export const DarkMode: StoryObj = {
  render: () => (
    <Card sx={{ bgcolor: '#121212', color: 'white' }}>
      <CardContent>
        <Typography>تم دارک</Typography>
        <Button variant="contained" color="primary">دکمه</Button>
        <Switch />
      </CardContent>
    </Card>
  ),
};