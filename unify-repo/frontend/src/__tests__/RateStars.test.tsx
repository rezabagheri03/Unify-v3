import { render, screen, fireEvent, waitFor } from '@testing-library/react';

jest.mock('../api/client', () => ({
  __esModule: true,
  default: { post: jest.fn() },
  apiErrorMessage: (_e: unknown, f: string) => f,
}));

import api from '../api/client';
import RateStars from '../components/RateStars';
import { SyncQueue } from '../db/idb';

const mockedPost = api.post as jest.Mock;

describe('RateStars (TODO-028 producer)', () => {
  beforeEach(async () => {
    jest.clearAllMocks();
    jest.restoreAllMocks();
    await SyncQueue.clearAll();
  });

  test('offline rating is queued into the SyncQueue instead of posted', async () => {
    jest.spyOn(navigator, 'onLine', 'get').mockReturnValue(false);
    const onMessage = jest.fn();

    render(<RateStars resourceId="res-1" average={3} count={2} onMessage={onMessage} />);
    fireEvent.click(screen.getAllByRole('radio')[3]); // 4 stars

    await waitFor(async () => {
      const queue = await SyncQueue.getAll();
      expect(queue).toHaveLength(1);
      expect(queue[0]).toMatchObject({
        type: 'rating',
        endpoint: '/resources/res-1/rating',
        payload: { rating: 4 },
        status: 'pending',
      });
    });
    expect(onMessage).toHaveBeenCalledWith(expect.stringContaining('صف همگام‌سازی'));
    expect(mockedPost).not.toHaveBeenCalled();
  });

  test('online rating posts immediately and reports the fresh average', async () => {
    jest.spyOn(navigator, 'onLine', 'get').mockReturnValue(true);
    mockedPost.mockResolvedValue({ data: { average: 4.2 } });
    const onRated = jest.fn();

    render(<RateStars resourceId="res-2" average={3} count={2} onRated={onRated} />);
    fireEvent.click(screen.getAllByRole('radio')[4]); // 5 stars

    await waitFor(() => {
      expect(mockedPost).toHaveBeenCalledWith('/resources/res-2/rating', { rating: 5 });
      expect(onRated).toHaveBeenCalledWith(4.2);
    });
    expect(await SyncQueue.getAll()).toHaveLength(0);
  });

  test('network-level failure (no HTTP response) falls back to the queue', async () => {
    jest.spyOn(navigator, 'onLine', 'get').mockReturnValue(true);
    // axios network failure shape: request set, response absent
    mockedPost.mockRejectedValue({ request: {}, message: 'Network Error' });

    render(<RateStars resourceId="res-3" />);
    fireEvent.click(screen.getAllByRole('radio')[2]); // 3 stars

    await waitFor(async () => {
      const queue = await SyncQueue.getAll();
      expect(queue).toHaveLength(1);
      expect(queue[0]).toMatchObject({ type: 'rating', payload: { rating: 3 }, status: 'pending' });
    });
  });
});
