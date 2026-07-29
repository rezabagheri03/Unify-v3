import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import AssignmentTrackerList from '../screens/Student/AssignmentTrackerList';

describe('AssignmentTrackerList', () => {
  test('renders assignment tracker title', () => {
    render(
      <BrowserRouter>
        <AssignmentTrackerList />
      </BrowserRouter>
    );

    expect(screen.getByText(/ردیاب تکالیف/i)).toBeInTheDocument();
  });
});