import type { Meta, StoryObj } from '@storybook/react';
import AssignmentCard from '../src/components/AssignmentCard';

const meta: Meta<typeof AssignmentCard> = { title: 'Unify/AssignmentCard', component: AssignmentCard };
export default meta;
type Story = StoryObj<typeof AssignmentCard>;

export const Graded: Story = { args: { assignment: { id: '1', title: 'تمرین ۱', status: 'graded', grade: 18.5, shamsi_original: '1403/08/15' } } };
export const Late: Story = { args: { assignment: { id: '2', title: 'پروژه', status: 'late', due_date_g: '2024-10-01' } } };
