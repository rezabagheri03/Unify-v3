import type { Meta, StoryObj } from '@storybook/react';
import OfflineQueueRow from '../src/components/OfflineQueueRow';

const meta: Meta<typeof OfflineQueueRow> = { title: 'Unify/OfflineQueueRow', component: OfflineQueueRow };
export default meta;
type Story = StoryObj<typeof OfflineQueueRow>;

export const Pending: Story = { args: { item: { id: 1, type: 'rating', status: 'pending', created_at: '2024-09-21T10:00:00Z' } } };
export const Failed: Story = { args: { item: { id: 2, type: 'ticket_reply', status: 'failed', last_error: 'network', created_at: '2024-09-21T10:00:00Z' } } };
