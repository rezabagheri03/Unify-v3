import type { Meta, StoryObj } from '@storybook/react';
import FileCard from '../src/components/FileCard';

const meta: Meta<typeof FileCard> = { title: 'Unify/FileCard', component: FileCard };
export default meta;
type Story = StoryObj<typeof FileCard>;

export const ProfessorPdf: Story = {
  args: { id: '1', title: 'جزوه فصل ۳', author: 'دکتر رضایی', average_rating: 4.2, rating_count: 15, download_count: 120, badge_type: 'professor', mime: 'application/pdf' },
};
export const StudentDocx: Story = {
  args: { id: '2', title: 'خلاصه نکات', author: 'سارا احمدی', average_rating: null, rating_count: 0, download_count: 3, badge_type: null, mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
};
