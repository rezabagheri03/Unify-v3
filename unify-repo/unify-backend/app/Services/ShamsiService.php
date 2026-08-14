<?php

namespace App\Services;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class ShamsiService
{
    public static function toGregorian(string $shamsiDate): string
    {
        try {
            return Jalalian::fromFormat('Y/m/d', $shamsiDate)->toCarbon()->toDateTimeString();
        } catch (\Exception $e) {
            return now()->toDateTimeString();
        }
    }

    /**
     * @param  \Carbon\Carbon|\DateTime|string|null  $gregorianDate
     */
    public static function toShamsi(mixed $gregorianDate): string
    {
        if (empty($gregorianDate)) {
            return '';
        }
        try {
            $carbon = $gregorianDate instanceof Carbon ? $gregorianDate : Carbon::parse($gregorianDate);
            return Jalalian::fromCarbon($carbon)->format('Y/m/d');
        } catch (\Exception $e) {
            return '1403/01/01';
        }
    }

    /**
     * Round-2 fix: this called Jalalian::isValid(), which DOES NOT EXIST in
     * morilog/jalali v3 — every caller (spec-import exam dates, semester
     * creation) hit a fatal Error. Validate by parsing instead.
     */
    public static function isValid(string $shamsiDate): bool
    {
        try {
            Jalalian::fromFormat('Y/m/d', $shamsiDate);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
