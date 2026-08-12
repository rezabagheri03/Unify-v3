import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import ConfirmationModal from '../src/components/ConfirmationModal';

const meta: Meta<typeof ConfirmationModal> = { title: 'Unify/ConfirmationModal', component: ConfirmationModal };
export default meta;
type Story = StoryObj;

export const RequiresTyping: Story = {
  render: () => {
    const [open, setOpen] = useState(true);
    return <ConfirmationModal open={open} title="بن کردن کاربر" message="این عملیات غیرقابل بازگشت است" requireText="DELETE" onConfirm={() => setOpen(false)} onClose={() => setOpen(false)} />;
  },
};
