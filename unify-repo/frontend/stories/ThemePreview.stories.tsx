import type { Meta, StoryObj } from '@storybook/react';
import ThemePreview from '../src/components/ThemePreview';

const meta: Meta<typeof ThemePreview> = { title: 'Unify/ThemePreview', component: ThemePreview };
export default meta;
type Story = StoryObj<typeof ThemePreview>;

export const Default: Story = { args: { active: 'Unify Blue', onSelect: () => {} } };
