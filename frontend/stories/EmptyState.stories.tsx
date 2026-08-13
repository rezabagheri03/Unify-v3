import type { Meta, StoryObj } from '@storybook/react';
import EmptyState from '../src/components/EmptyState';

const meta: Meta<typeof EmptyState> = { title: 'Unify/EmptyState', component: EmptyState };
export default meta;
type Story = StoryObj<typeof EmptyState>;

export const WithCta: Story = { args: { title: 'برنامه شما خالی است', actionLabel: 'انتخاب واحد', onAction: () => {} } };
