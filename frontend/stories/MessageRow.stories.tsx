import type { Meta, StoryObj } from '@storybook/react';
import MessageRow from '../src/components/MessageRow';

const meta: Meta<typeof MessageRow> = { title: 'Unify/MessageRow', component: MessageRow };
export default meta;
type Story = StoryObj<typeof MessageRow>;

export const Edited: Story = { args: { message: { id: '1', subject: 'تغییر برنامه', body: 'کلاس لغو شد', sender: { first_name: 'دکتر', last_name: 'رضایی' }, is_edited: true, sent_at: '2024-09-21T10:00:00Z', priority: 'high' } } };
