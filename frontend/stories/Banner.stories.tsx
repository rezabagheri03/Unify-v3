import type { Meta, StoryObj } from '@storybook/react';
import Banner from '../src/components/Banner';

const meta: Meta<typeof Banner> = { title: 'Unify/Banner', component: Banner };
export default meta;
type Story = StoryObj<typeof Banner>;

export const Critical: Story = { args: { tone: 'critical', children: 'تداخل زمانی با ریاضی ۲' } };
export const Warning: Story = { args: { tone: 'warning', children: 'پیش‌نیاز را پاس نکرده‌اید' } };
export const Intranet: Story = { args: { tone: 'intranet' } };
export const Honor: Story = { args: { tone: 'honor' } };
export const Offline: Story = { args: { tone: 'offline' } };
