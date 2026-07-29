import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import SchedulerA from '../screens/Student/SchedulerA';

describe('SchedulerA', () => {
  test('renders phase A title and search input', () => {
    render(
      <BrowserRouter>
        <SchedulerA />
      </BrowserRouter>
    );

    expect(screen.getByText(/فاز A - جستجو و انتخاب موقت/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/جستجو/i)).toBeInTheDocument();
  });
});