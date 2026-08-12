import type { Meta, StoryObj } from '@storybook/react';
import { CourseCardSkeleton, FileCardSkeleton } from '../src/components/Skeletons';

const meta = { title: 'Unify/Skeletons' } as Meta;
export default meta;
type Story = StoryObj;

export const Courses: Story = { render: () => <CourseCardSkeleton count={3} /> };
export const Files: Story = { render: () => <FileCardSkeleton count={3} /> };
