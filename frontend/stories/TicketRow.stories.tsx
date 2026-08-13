import type { Meta, StoryObj } from '@storybook/react';
import TicketRow from '../src/components/TicketRow';

const meta: Meta<typeof TicketRow> = { title: 'Unify/TicketRow', component: TicketRow };
export default meta;
type Story = StoryObj<typeof TicketRow>;

export const Open: Story = { args: { ticket: { id: '1', subject: 'مشکل ثبت‌نام', description: 'نمیتوانم واحد اضافه کنم', department: 'education', status: 'open', created_at: '2024-09-21T10:00:00Z' } } };
