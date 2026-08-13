import type { Meta, StoryObj } from '@storybook/react';
import { ListItem, ListItemText, Badge } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/NotificationRow',
  component: ListItem,
};

export default meta;

export const Unread: StoryObj = {
  render: () => (
    <ListItem>
      <ListItemText primary="تغییر زمان کلاس" secondary="۱۰ دقیقه پیش" />
      <Badge color="primary" badgeContent="جدید" />
    </ListItem>
  ),
};

export const Read: StoryObj = {
  render: () => (
    <ListItem>
      <ListItemText primary="منبع جدید آپلود شد" secondary="دیروز" />
    </ListItem>
  ),
};

export const Critical: StoryObj = {
  render: () => (
    <ListItem>
      <ListItemText primary="دوره grace در حال اتمام" secondary="۱ ساعت پیش" />
      <Badge color="error" badgeContent="بحرانی" />
    </ListItem>
  ),
};