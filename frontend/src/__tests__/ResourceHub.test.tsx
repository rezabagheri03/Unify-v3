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

import ResourceHubList from '../screens/Student/ResourceHubList';

describe('ResourceHubList', () => {
  test('renders resource hub title', () => {
    render(
      <BrowserRouter>
        <ResourceHubList />
      </BrowserRouter>
    );

    expect(screen.getByText(/مرکز منابع/i)).toBeInTheDocument();
  });
});
