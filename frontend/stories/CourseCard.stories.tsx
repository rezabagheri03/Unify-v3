import type { Meta, StoryObj } from '@storybook/react';
import CourseCard from '../src/components/CourseCard';

const meta: Meta<typeof CourseCard> = { title: 'Unify/CourseCard', component: CourseCard };
export default meta;
type Story = StoryObj<typeof CourseCard>;

export const Default: Story = {
  args: {
    spec: {
      id: '1', course: { name: 'ریاضی ۲', code: 'CS102', credits: 3 },
      professor: { first_name: 'دکتر', last_name: 'رضایی' },
      day_of_week: 'شنبه', time_start: '08:00', time_end: '10:00',
      location: 'کلاس ۱۰۱', shamsi_final: '1403/04/22',
    },
  },
};
