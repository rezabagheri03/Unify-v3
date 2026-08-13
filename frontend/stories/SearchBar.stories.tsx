import type { Meta, StoryObj } from '@storybook/react';
import SearchBar from '../src/components/SearchBar';

const meta: Meta<typeof SearchBar> = { title: 'Unify/SearchBar', component: SearchBar };
export default meta;
type Story = StoryObj<typeof SearchBar>;

export const Default: Story = { args: { placeholder: 'جستجوی درس...', onChange: () => {} } };
