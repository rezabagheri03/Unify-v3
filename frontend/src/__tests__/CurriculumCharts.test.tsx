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

import CurriculumCharts from '../screens/Student/CurriculumCharts';

describe('CurriculumCharts', () => {
  test('renders curriculum charts title', () => {
    render(
      <BrowserRouter>
        <CurriculumCharts />
      </BrowserRouter>
    );

    expect(screen.getByText(/نمودار درسی/i)).toBeInTheDocument();
  });
});
