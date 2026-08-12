import type { Meta, StoryObj } from '@storybook/react';
import RatingStars from '../src/components/RatingStars';

const meta: Meta<typeof RatingStars> = { title: 'Unify/RatingStars', component: RatingStars };
export default meta;
type Story = StoryObj<typeof RatingStars>;

export const Interactive: Story = { args: { value: 3, readonly: false } };
export const Readonly: Story = { args: { value: 5, readonly: true } };
