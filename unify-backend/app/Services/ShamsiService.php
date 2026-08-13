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

    public static function isValid(string $shamsiDate): bool
    {
        return Jalalian::isValid($shamsiDate);
    }
}
