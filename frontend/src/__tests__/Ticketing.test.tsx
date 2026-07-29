import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import TicketingList from '../screens/Student/TicketingList';

describe('TicketingList', () => {
  test('renders ticketing page title and new ticket button', () => {
    render(
      <BrowserRouter>
        <TicketingList />
      </BrowserRouter>
    );

    expect(screen.getByText(/تیکت‌ها/i)).toBeInTheDocument();
    expect(screen.getByText(/تیکت جدید/i)).toBeInTheDocument();
  });
});