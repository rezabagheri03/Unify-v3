<?php

namespace App\Services;

class InputSanitizer
{
    public static function clean(string $input, int $maxLength = 2000): string
    {
        $input = strip_tags($input);
        $input = trim($input);
        $input = mb_substr($input, 0, $maxLength);
        return $input;
    }

    public static function cleanArray(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = self::clean($data[$field]);
            }
        }
        return $data;
    }
}