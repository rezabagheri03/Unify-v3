<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Round-2 refactor (audit V2-05/V2-11): the atomic daily-quota claim that
 * post-audit F-16 added inside ResourceController is promoted to the shared
 * primitive for EVERY daily cap — uploads AND downloads AND versions AND
 * tickets (tickets were still check-then-act).
 *
 * Atomics: firstOrCreate handles the first-claim race via the unique
 * (user, date) index; the actual gate runs inside a transaction with the
 * counter row locked, so two parallel claims at max-1 can no longer both pass.
 */
class DailyQuota
{
    public static function claim(string $modelClass, string $userColumn, string $userId, int $max): bool
    {
        $today = now()->toDateString();
        try {
            $row = $modelClass::firstOrCreate([$userColumn => $userId, 'date' => $today], ['count' => 0]);
        } catch (QueryException $e) {
            $row = $modelClass::where($userColumn, $userId)->where('date', $today)->firstOrFail();
        }

        return DB::transaction(function () use ($modelClass, $row, $max) {
            $count = (int) $modelClass::whereKey($row->id)->lockForUpdate()->value('count');
            if ($count >= $max) {
                return false;
            }
            $modelClass::whereKey($row->id)->update(['count' => $count + 1]);
            return true;
        });
    }
}
