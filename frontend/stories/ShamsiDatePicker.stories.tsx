import type { Meta, StoryObj } from '@storybook/react';
import ShamsiDatePicker from '../src/components/ShamsiDatePicker';

const meta: Meta<typeof ShamsiDatePicker> = { title: 'Unify/ShamsiDatePicker', component: ShamsiDatePicker };
export default meta;
type Story = StoryObj<typeof ShamsiDatePicker>;

export const Valid: Story = { args: { value: '1403/08/15', onChange: () => {} } };
export const Invalid: Story = { args: { value: '1403/13/40', onChange: () => {} } };
