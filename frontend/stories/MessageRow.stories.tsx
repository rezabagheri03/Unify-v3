import type { Meta, StoryObj } from '@storybook/react';
import { ListItem, ListItemText, Avatar } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/MessageRow',
  component: ListItem,
};

export default meta;

export const Default: StoryObj = {
  render: () => (
    <ListItem>
      <Avatar>ع</Avatar>
      <ListItemText 
        primary="یادآوری کلاس فردا" 
        secondary="دکتر کریمی • ۲ ساعت پیش" 
      />
    </ListItem>
  ),
};

export const WithPriority: StoryObj = {
  render: () => (
    <ListItem>
      <Avatar>ا</Avatar>
      <ListItemText 
        primary="تغییر زمان امتحان" 
        secondary="استاد هوش مصنوعی • مهم" 
      />
    </ListItem>
  ),
};