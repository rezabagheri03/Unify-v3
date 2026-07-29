import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import CurriculumCharts from '../screens/Student/CurriculumCharts';

describe('CurriculumCharts', () => {
  test('renders curriculum charts title', () => {
    render(
      <BrowserRouter>
        <CurriculumCharts />
      </BrowserRouter>
    );

    expect(screen.getByText(/نمودارهای درسی/i)).toBeInTheDocument();
  });
});