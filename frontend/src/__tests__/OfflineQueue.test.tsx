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

import SettingsOfflineQueue from '../screens/Student/Settings/OfflineQueue';

describe('OfflineQueue', () => {
  test('renders offline queue title and sync button', () => {
    render(
      <BrowserRouter>
        <SettingsOfflineQueue />
      </BrowserRouter>
    );

    expect(screen.getByText(/صف آفلاین/i)).toBeInTheDocument();
    expect(screen.getByText(/همگام‌سازی دستی/i)).toBeInTheDocument();
  });
});
