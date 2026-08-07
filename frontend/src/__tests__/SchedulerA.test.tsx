import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
jest.mock('../api/client', () => ({
  __esModule: true,
  default: {
    get: jest.fn().mockResolvedValue({ data: [] }),
    post: jest.fn().mockResolvedValue({ data: {} }),
    delete: jest.fn().mockResolvedValue({ data: {} }),
  },
  apiErrorMessage: (e: any, f: string) => f,
}));

import SchedulerA from '../screens/Student/SchedulerA';

describe('SchedulerA', () => {
  test('renders phase A title and search input', () => {
    render(
      <BrowserRouter>
        <SchedulerA />
      </BrowserRouter>
    );

    expect(screen.getByText(/فاز A/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/جستجوی درس/i)).toBeInTheDocument();
    expect(screen.getByText(/وضعیت تحصیلی \(سیستم افتخار/i)).toBeInTheDocument();
  });
});
