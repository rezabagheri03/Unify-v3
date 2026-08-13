import type { Meta, StoryObj } from '@storybook/react';
import StatusBadge from '../src/components/StatusBadge';

const meta: Meta<typeof StatusBadge> = { title: 'Unify/StatusBadge', component: StatusBadge };
export default meta;
type Story = StoryObj<typeof StatusBadge>;

export const TicketStates: Story = { render: () => (<div style={{display:'flex',gap:8}}>{['open','in_progress','answered','closed'].map(s => <StatusBadge key={s} status={s} />)}</div>) };
export const ResourceStates: Story = { render: () => (<div style={{display:'flex',gap:8}}>{['pending','approved','rejected'].map(s => <StatusBadge key={s} status={s} />)}</div>) };
