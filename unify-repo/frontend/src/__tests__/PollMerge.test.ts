import { renderHook, act } from '@testing-library/react';

jest.mock('../api/client', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), patch: jest.fn(), delete: jest.fn() },
  apiErrorMessage: (_e: unknown, f?: string) => f ?? 'error',
}));

import api from '../api/client';
import { useNotificationsPolling } from '../api/polling';
import { useAuthStore } from '../stores/authStore';
import { makeUser, makeNotification } from '../test/factories';

const mockedGet = api.get as jest.Mock;

/**
 * TODO-048: notification poll merge — the app's heartbeat (F15). Pins the
 * three merge contracts: fresh items prepend, duplicates by id are dropped,
 * the list is capped at 50.
 */
describe('useNotificationsPolling merge', () => {
  beforeEach(() => {
    jest.useFakeTimers();
    jest.clearAllMocks();
    localStorage.clear();
    useAuthStore.setState({ user: makeUser(), token: 't', isAuthenticated: true });
  });

  afterEach(() => {
    jest.useRealTimers();
    act(() => { useAuthStore.setState({ user: null, token: null, isAuthenticated: false }); });
  });

  const flushMicrotasks = async () => { await act(async () => { await Promise.resolve(); }); };

  it('populates from the immediate first poll', async () => {
    mockedGet.mockResolvedValueOnce({ data: [makeNotification({ id: 'n1' }), makeNotification({ id: 'n2' })] });

    const { result } = renderHook(() => useNotificationsPolling());
    await flushMicrotasks();

    expect(mockedGet).toHaveBeenCalledTimes(1);
    expect(result.current.map((n: any) => n.id)).toEqual(['n1', 'n2']);
  });

  it('drops duplicate ids and prepends fresh items on the next interval', async () => {
    mockedGet
      .mockResolvedValueOnce({ data: [makeNotification({ id: 'n1' }), makeNotification({ id: 'n2' })] })
      .mockResolvedValueOnce({ data: [makeNotification({ id: 'n2' }), makeNotification({ id: 'n3' })] });

    const { result } = renderHook(() => useNotificationsPolling());
    await flushMicrotasks();

    act(() => { jest.advanceTimersByTime(30000); });
    await flushMicrotasks();

    expect(result.current.map((n: any) => n.id)).toEqual(['n3', 'n1', 'n2']);
    expect(mockedGet).toHaveBeenCalledTimes(2);
  });

  it('caps the merged list at 50', async () => {
    const first = [makeNotification({ id: 'n0' })];
    const flood = Array.from({ length: 60 }, (_, i) => makeNotification({ id: `f${i}` }));
    mockedGet
      .mockResolvedValueOnce({ data: first })
      .mockResolvedValueOnce({ data: flood });

    const { result } = renderHook(() => useNotificationsPolling());
    await flushMicrotasks();

    act(() => { jest.advanceTimersByTime(30000); });
    await flushMicrotasks();

    expect(result.current).toHaveLength(50);
    expect(result.current[0].id).toBe('f0'); // newest batch leads the list
  });
});
