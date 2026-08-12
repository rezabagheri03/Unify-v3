import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import StudentDashboard from '../screens/Student/Dashboard';

jest.mock('../api/client', () => ({
  __esModule: true,
  default: {
    get: jest.fn().mockResolvedValue({ data: [] }),
    post: jest.fn().mockResolvedValue({ data: {} }),
    delete: jest.fn().mockResolvedValue({ data: {} }),
  },
  apiErrorMessage: (e: any, f: string) => f,
}));


describe('StudentDashboard', () => {
  test('renders welcome message and navigation cards', () => {
    render(
      <BrowserRouter>
        <StudentDashboard />
      </BrowserRouter>
    );

    expect(screen.getByText(/داشبورد دانشجو/i)).toBeInTheDocument();
    expect(screen.getByText(/لیست موقت/i)).toBeInTheDocument();
    expect(screen.getByText(/مرکز منابع/i)).toBeInTheDocument();
  });
});
