import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import FormsCalendar from '../screens/Student/FormsCalendar';

describe('FormsCalendar', () => {
  test('renders forms and calendar tabs', () => {
    render(
      <BrowserRouter>
        <FormsCalendar />
      </BrowserRouter>
    );

    expect(screen.getByText(/فرم‌ها/i)).toBeInTheDocument();
    expect(screen.getByText(/تقویم/i)).toBeInTheDocument();
    expect(screen.getByText(/تابلو اعلانات/i)).toBeInTheDocument();
    expect(screen.getByText(/سوالات متداول/i)).toBeInTheDocument();
  });
});