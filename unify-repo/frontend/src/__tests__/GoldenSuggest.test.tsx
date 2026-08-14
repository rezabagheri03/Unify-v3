import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';

jest.mock('../api/client', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), patch: jest.fn(), delete: jest.fn() },
  apiErrorMessage: (_e: unknown, f?: string) => f ?? 'خطا',
}));

import api from '../api/client';
import GoldenSuggest from '../components/GoldenSuggest';
import { makeSpec, makeSuggestion } from '../test/factories';

const mockedGet = api.get as jest.Mock;

/**
 * TODO-048: golden-schedule render (TODO-027/F04 UI) — the suggestion cards
 * must render score/credits/explanation and the spec rows, mark cached
 * results, and degrade cleanly on empty/error.
 */
describe('GoldenSuggest', () => {
  beforeEach(() => jest.clearAllMocks());

  const specs = [
    makeSpec(),
    makeSpec({ professor: { id: 'P1002', first_name: 'مریم', last_name: 'کریمی' }, course: { name: 'فیزیک ۱' }, course_id: 'CS102' }),
  ];

  it('lists the professor preference checkboxes built from specs', () => {
    render(<GoldenSuggest specs={specs} />);
    expect(screen.getByText('احمد رضایی')).toBeInTheDocument();
    expect(screen.getByText('مریم کریمی')).toBeInTheDocument();
  });

  it('renders suggestion cards with score, credits, explanation and spec rows', async () => {
    mockedGet.mockResolvedValueOnce({
      data: { suggestions: [makeSuggestion({ score: 92, credits: 14 })], cached: true },
    });

    render(<GoldenSuggest specs={specs} />);
    fireEvent.click(screen.getByText('دریافت پیشنهاد'));

    await waitFor(() => expect(screen.getByText(/امتیاز 92/)).toBeInTheDocument());
    expect(screen.getByText('14 واحد')).toBeInTheDocument();
    expect(screen.getByText('کمترین فاصله خالی بین کلاس‌ها')).toBeInTheDocument();
    expect(screen.getAllByText(/ریاضی ۱|فیزیک ۱/).length).toBeGreaterThan(0);
    expect(screen.getByText('از حافظه موقت')).toBeInTheDocument(); // cached flag surfaced
    expect(mockedGet).toHaveBeenCalledWith('/golden-schedule', expect.objectContaining({ params: expect.any(Object) }));
  });

  it('shows guidance when no schedule fits', async () => {
    mockedGet.mockResolvedValueOnce({ data: { suggestions: [], cached: false } });

    render(<GoldenSuggest specs={specs} />);
    fireEvent.click(screen.getByText('دریافت پیشنهاد'));

    await waitFor(() => expect(screen.getByText(/چینش سازگاری یافت نشد/)).toBeInTheDocument());
  });

  it('surfaces an error message instead of results on API failure', async () => {
    mockedGet.mockRejectedValueOnce(new Error('network down'));

    render(<GoldenSuggest specs={specs} />);
    fireEvent.click(screen.getByText('دریافت پیشنهاد'));

    await waitFor(() => expect(screen.getByText('خطا')).toBeInTheDocument());
    expect(screen.queryByText(/امتیاز/)).not.toBeInTheDocument();
  });
});
