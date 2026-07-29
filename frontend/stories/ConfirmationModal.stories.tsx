import type { Meta, StoryObj } from '@storybook/react';
import { Dialog, DialogTitle, DialogContent, DialogActions, Button, Typography } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/ConfirmationModal',
  component: Dialog,
};

export default meta;

export const DeleteResource: StoryObj = {
  render: () => (
    <Dialog open>
      <DialogTitle>حذف منبع؟</DialogTitle>
      <DialogContent>
        <Typography>آیا مطمئن هستید که می‌خواهید این منبع را حذف کنید؟</Typography>
      </DialogContent>
      <DialogActions>
        <Button>لغو</Button>
        <Button color="error" variant="contained">حذف</Button>
      </DialogActions>
    </Dialog>
  ),
};