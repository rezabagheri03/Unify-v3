import React, { useState } from 'react';
import Box from '@mui/material/Box';
import Rating from '@mui/material/Rating';
import Typography from '@mui/material/Typography';
import api from '../api/client';
import { SyncQueue } from '../db/idb';

interface RateStarsProps {
  resourceId: string;
  average?: number | null;
  count?: number;
  /** parent snackbar hook */
  onMessage?: (msg: string) => void;
  /** called with the new server average after a successful online rating */
  onRated?: (average: number) => void;
}

/**
 * Interactive star rating with offline support (TODO-028 / F19).
 *
 * Online: POSTs immediately and shows the fresh server average.
 * Offline (or network-level failure with no HTTP response): the rating is
 * added to the IndexedDB SyncQueue as a 'rating' intent and replayed by
 * utils/offlineSync.ts when connectivity returns. The backend rating
 * endpoint is an upsert per (student, family), so a replay after a
 * partially-rolled-back online attempt is safe.
 */
export default function RateStars({ resourceId, average, count, onMessage, onRated }: RateStarsProps) {
  const [myRating, setMyRating] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);

  const rate = async (value: number | null) => {
    if (!value || busy) return;
    setMyRating(value);

    if (!navigator.onLine) {
      await SyncQueue.add({
        type: 'rating',
        endpoint: `/resources/${resourceId}/rating`,
        payload: { rating: value },
      });
      onMessage?.('آفلاین هستید — امتیاز در صف همگام‌سازی ذخیره شد');
      return;
    }

    setBusy(true);
    try {
      const res = await api.post(`/resources/${resourceId}/rating`, { rating: value });
      onRated?.(res.data?.average);
      onMessage?.('امتیاز شما ثبت شد');
    } catch (err: any) {
      if (!err?.response) {
        // Network-level failure (tunnel drop, captive portal): queue it.
        await SyncQueue.add({
          type: 'rating',
          endpoint: `/resources/${resourceId}/rating`,
          payload: { rating: value },
        });
        onMessage?.('اتصال برقرار نشد — امتیاز در صف همگام‌سازی ذخیره شد');
      } else {
        onMessage?.(err.response.data?.message || 'خطا در ثبت امتیاز');
        setMyRating(null);
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 0.5 }}>
      <Rating
        size="small"
        value={myRating ?? (average ? Math.round(average) : null)}
        onChange={(_e, v) => rate(v)}
        disabled={busy}
        aria-label="امتیاز به منبع"
      />
      <Typography variant="caption" color="text.secondary">
        {average ?? '—'} ({count ?? 0})
      </Typography>
    </Box>
  );
}
