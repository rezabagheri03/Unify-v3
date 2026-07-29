import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import NotificationsSettings from '../screens/Student/Settings/Notifications';

describe('NotificationsSettings', () => {
  test('renders notifications polling settings', () => {
    render(
      <BrowserRouter>
        <NotificationsSettings />
      </BrowserRouter>
    );

    expect(screen.getByText(/اعلان‌ها/i)).toBeInTheDocument();
    expect(screen.getByText(/Polling ۳۰ ثانیه‌ای/i)).toBeInTheDocument();
  });
});