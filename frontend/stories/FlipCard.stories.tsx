import type { Meta, StoryObj } from '@storybook/react';
import { ExamFlipCard } from '../src/components/FlipCard';

const meta: Meta = { title: 'Unify/FlipCard', component: ExamFlipCard };
export default meta;
type Story = StoryObj;

export const Flipped: Story = { render: () => <ExamFlipCard flipped courseName="فیزیک ۱" examDate="1403/04/20" midtermDate="1403/03/21" /> };
export const Front: Story = { render: () => <ExamFlipCard courseName="فیزیک ۱" examDate="1403/04/20" /> };
