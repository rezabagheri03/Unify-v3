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

import SettingsNotifications from '../screens/Student/Settings/Notifications';

describe('NotificationsSettings', () => {
  test('renders notifications settings', () => {
    render(
      <BrowserRouter>
        <SettingsNotifications />
      </BrowserRouter>
    );

    expect(screen.getAllByText(/اعلان‌ها/i).length).toBeGreaterThan(0);
    expect(screen.getByText(/۳۰ ثانیه/i)).toBeInTheDocument();
  });
});
