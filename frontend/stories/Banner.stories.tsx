import type { Meta, StoryObj } from '@storybook/react';
import { Alert, AlertTitle } from '@mui/material';

const meta: Meta<any> = {
  title: 'Components/Banner',
  component: Alert,
  tags: ['autodocs'],
};

export default meta;

export const Warning: StoryObj = {
  args: {
    severity: 'warning',
    children: (
      <>
        <AlertTitle>هشدار</AlertTitle>
        انتخاب واحد شما با تداخل زمانی مواجه است.
      </>
    ),
  },
};

export const Success: StoryObj = {
  args: {
    severity: 'success',
    children: 'انتخاب واحد با موفقیت نهایی شد.',
  },
};

export const Error: StoryObj = {
  args: {
    severity: 'error',
    children: 'خطا در برقراری ارتباط با سرور.',
  },
};