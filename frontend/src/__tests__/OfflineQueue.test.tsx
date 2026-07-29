import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import OfflineQueue from '../screens/Student/Settings/OfflineQueue';

describe('OfflineQueue', () => {
  test('renders offline queue title and sync button', () => {
    render(
      <BrowserRouter>
        <OfflineQueue />
      </BrowserRouter>
    );

    expect(screen.getByText(/صف صف همگام‌سازی آفلاین/i)).toBeInTheDocument();
    expect(screen.getByText(/همگام‌سازی دستی/i)).toBeInTheDocument();
  });
});