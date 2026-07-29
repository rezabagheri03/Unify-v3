import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import Login from '../screens/Auth/Login';

describe('Login Page', () => {
  const renderLogin = () => {
    render(
      <BrowserRouter>
        <Login />
      </BrowserRouter>
    );
  };

  test('renders login form elements', () => {
    renderLogin();

    expect(screen.getByPlaceholderText(/شماره دانشجویی/i)).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/رمز عبور/i)).toBeInTheDocument();
    expect(screen.getByText(/ورود/i)).toBeInTheDocument();
  });

  test('allows typing in username and password fields', () => {
    renderLogin();

    const usernameInput = screen.getByPlaceholderText(/شماره دانشجویی/i);
    const passwordInput = screen.getByPlaceholderText(/رمز عبور/i);

    fireEvent.change(usernameInput, { target: { value: '400123456' } });
    fireEvent.change(passwordInput, { target: { value: 'mypassword' } });

    expect(usernameInput).toHaveValue('400123456');
    expect(passwordInput).toHaveValue('mypassword');
  });
});