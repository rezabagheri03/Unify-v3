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

import TicketingList from '../screens/Student/TicketingList';

describe('TicketingList', () => {
  test('renders ticketing title and new ticket button', () => {
    render(
      <BrowserRouter>
        <TicketingList />
      </BrowserRouter>
    );

    expect(screen.getAllByText(/تیکت/).length).toBeGreaterThan(0);
    expect(screen.getByText(/تیکت جدید/i)).toBeInTheDocument();
  });
});
