<?php

namespace App\Support;

class Rupiah
{
    public static function format(float|int|string|null $value, bool $withPrefix = true): string
    {
        $number = (float) $value;

        return ($withPrefix ? 'Rp ' : '').number_format($number, 0, ',', '.');
    }

    public static function parse(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', $value) ?? '0';
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}
