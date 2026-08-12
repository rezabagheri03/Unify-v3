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

import FormsCalendar from '../screens/Student/FormsCalendar';

describe('FormsCalendar', () => {
  test('renders forms and calendar tabs', () => {
    render(
      <BrowserRouter>
        <FormsCalendar />
      </BrowserRouter>
    );

    expect(screen.getAllByText(/فرم‌ها/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/تقویم/).length).toBeGreaterThan(0);
    expect(screen.getByText(/اعلان‌ها/)).toBeInTheDocument();
    expect(screen.getByText(/سوالات متداول/i)).toBeInTheDocument();
  });
});
