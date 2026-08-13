import React from 'react';
import { render, screen, act, waitFor } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';

jest.mock('../api/client', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), patch: jest.fn(), delete: jest.fn() },
  apiErrorMessage: (_e: unknown, f?: string) => f ?? 'error',
}));

import api from '../api/client';
import { useAuthStore } from '../stores/authStore';
import { ProtectedRoute } from '../components/ProtectedRoute';
import { storageGet, storageSet } from '../db/safeStorage';
import { makeUser } from '../test/factories';

const mockedGet = api.get as jest.Mock;
const STORE_KEY = 'unify-auth';

const flush = async (ms = 25) => {
  await act(async () => { await new Promise((r) => setTimeout(r, ms)); });
};

// fake-indexeddb transactions settle on wall-clock time, not macrotask ticks —
// poll until the async persist write actually lands (bounded).
const waitForPersist = async (key: string, timeoutMs = 2000): Promise<string | null> => {
  const start = Date.now();
  for (;;) {
    const v = await storageGet(key);
    if (v != null) return v;
    if (Date.now() - start > timeoutMs) return null;
    await flush(10);
  }
};

/**
 * TODO-048: login-restore is the app-boot contract — the session must come
 * back from persisted storage with NO network call (the frontend never calls
 * /users/me; restored trust is the zustand persist payload).
 */
describe('login restore', () => {
  beforeEach(async () => {
    jest.clearAllMocks();
    useAuthStore.setState({ user: null, token: null, isAuthenticated: false, mustChangePassword: false });
    await useAuthStore.persist.clearStorage();
    await flush(); // let any pending persist writes settle before the test body
  });

  it('persists an authenticated payload to durable storage on login', async () => {
    await act(async () => { useAuthStore.getState().login(makeUser(), 'tok-abc'); });

    const raw = await waitForPersist(STORE_KEY);
    expect(raw).toBeTruthy();
    const persisted = JSON.parse(raw as string);
    expect(persisted.state.token).toBe('tok-abc');
    expect(persisted.state.isAuthenticated).toBe(true);
    expect(persisted.state.user.id).toBe('400100001');
  });

  it('restores the session from storage on boot (reload path), with zero network', async () => {
    // Seed storage as a previous session left it, then boot-hydrate.
    await storageSet(STORE_KEY, JSON.stringify({
      state: { user: makeUser(), token: 'tok-xyz', isAuthenticated: true, mustChangePassword: false },
      version: 0,
    }));

    await act(async () => { await useAuthStore.persist.rehydrate(); });

    expect(useAuthStore.getState().isAuthenticated).toBe(true);
    expect(useAuthStore.getState().token).toBe('tok-xyz');
    expect(useAuthStore.getState().user?.id).toBe('400100001');
    expect(mockedGet).not.toHaveBeenCalled();
  });

  it('ProtectedRoute renders children for a hydrated session without /users/me', async () => {
    await act(async () => {
      useAuthStore.getState().login(makeUser(), 'tok-abc');
      await useAuthStore.persist.rehydrate();
    });

    render(
      <MemoryRouter initialEntries={['/secret']}>
        <Routes>
          <Route element={<ProtectedRoute />}>
            <Route path="/secret" element={<div>محتوای خصوصی</div>} />
          </Route>
          <Route path="/login" element={<div>صفحه ورود</div>} />
        </Routes>
      </MemoryRouter>
    );

    await waitFor(() => expect(screen.getByText('محتوای خصوصی')).toBeInTheDocument());
    expect(mockedGet).not.toHaveBeenCalled();
  });

  it('redirects to /login when nothing is persisted', async () => {
    await act(async () => { await useAuthStore.persist.rehydrate(); });

    render(
      <MemoryRouter initialEntries={['/secret']}>
        <Routes>
          <Route element={<ProtectedRoute />}>
            <Route path="/secret" element={<div>محتوای خصوصی</div>} />
          </Route>
          <Route path="/login" element={<div>صفحه ورود</div>} />
        </Routes>
      </MemoryRouter>
    );

    await waitFor(() => expect(screen.getByText('صفحه ورود')).toBeInTheDocument());
  });
});
