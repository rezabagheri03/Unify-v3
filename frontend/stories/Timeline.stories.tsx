import type { Meta, StoryObj } from '@storybook/react';
import Timeline from '../src/components/Timeline';

const meta: Meta<typeof Timeline> = { title: 'Unify/Timeline', component: Timeline };
export default meta;
type Story = StoryObj<typeof Timeline>;

export const Default: Story = {
  args: {
    events: [
      { id: '1', title: 'شروع ثبت‌نام', description: 'آغاز ثبت‌نام', start_date_g: '2024-09-20', event_type: 'registration_open', color_code: '#4CAF50' },
      { id: '2', title: 'پایان ثبت‌نام', description: 'مهلت', start_date_g: '2024-09-28', event_type: 'registration_close', color_code: '#F44336' },
    ],
  },
};
