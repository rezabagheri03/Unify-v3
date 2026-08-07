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

import AssignmentTrackerList from '../screens/Student/AssignmentTrackerList';

describe('AssignmentTracker', () => {
  test('renders assignment tracker title', () => {
    render(
      <BrowserRouter>
        <AssignmentTrackerList />
      </BrowserRouter>
    );

    expect(screen.getByText(/تکالیف/i)).toBeInTheDocument();
  });
});
