import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Login from '../screens/Auth/Login';

jest.mock('../api/client', () => ({
  __esModule: true,
  default: { post: jest.fn().mockResolvedValue({ data: { user: {}, access_token: 't' } }) },
  apiErrorMessage: (e: any, f: string) => f,
}));

describe('Login Page', () => {
  const renderLogin = () =>
    render(
      <BrowserRouter>
        <Login />
      </BrowserRouter>
    );

  test('renders login form elements', () => {
    renderLogin();

    expect(screen.getByLabelText(/شماره دانشجویی \/ پرسنلی/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/رمز عبور/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /ورود/ })).toBeInTheDocument();
  });

  test('allows typing in username and password fields', () => {
    renderLogin();

    const usernameInput = screen.getByLabelText(/شماره دانشجویی \/ پرسنلی/i);
    const passwordInput = screen.getByLabelText(/رمز عبور/i);

    fireEvent.change(usernameInput, { target: { value: '400123456' } });
    fireEvent.change(passwordInput, { target: { value: 'mypassword' } });

    expect(usernameInput).toHaveValue('400123456');
    expect(passwordInput).toHaveValue('mypassword');
  });
});
