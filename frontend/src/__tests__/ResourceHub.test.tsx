import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
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