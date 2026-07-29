import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import StudentDashboard from '../screens/Student/Dashboard';

describe('StudentDashboard', () => {
  test('renders welcome message and navigation cards', () => {
    render(
      <BrowserRouter>
        <StudentDashboard />
      </BrowserRouter>
    );

    expect(screen.getByText(/داشبورد دانشجو/i)).toBeInTheDocument();
    expect(screen.getByText(/انتخاب واحد/i)).toBeInTheDocument();
    expect(screen.getByText(/مرکز منابع/i)).toBeInTheDocument();
    expect(screen.getByText(/اعلان‌ها/i)).toBeInTheDocument();
  });
});