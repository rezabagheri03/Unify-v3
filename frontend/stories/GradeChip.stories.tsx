import type { Meta, StoryObj } from '@storybook/react';
import GradeChip from '../src/components/GradeChip';

const meta: Meta<typeof GradeChip> = { title: 'Unify/GradeChip', component: GradeChip };
export default meta;
type Story = StoryObj<typeof GradeChip>;

export const Pass: Story = { args: { grade: 17.5 } };
export const Fail: Story = { args: { grade: 8 } };
export const Empty: Story = { args: { grade: null } };
