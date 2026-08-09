<?php

namespace App\Services;

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

    public static function toShamsi(string $gregorianDate): string
    {
        try {
            return Jalalian::fromCarbon(new \DateTime($gregorianDate))->format('Y/m/d');
        } catch (\Exception $e) {
            return '1403/01/01';
        }
    }

    public static function isValid(string $shamsiDate): bool
    {
        return Jalalian::isValid($shamsiDate);
    }
}