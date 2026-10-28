<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Exact decimal arithmetic for GEL amounts (decimal(12,2) columns) using bcmath.
 * Values are always numeric strings with two decimals, never floats.
 */
final class Money
{
    private const SCALE = 2;

    /**
     * @return numeric-string
     */
    public static function of(string|int|float|null $value): string
    {
        $value ??= '0';
        if (is_float($value)) {
            $value = number_format($value, self::SCALE, '.', '');
        }
        $value = (string) $value;

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Not a money amount: {$value}");
        }

        return bcadd($value, '0', self::SCALE);
    }

    /**
     * @return numeric-string
     */
    public static function add(string|int|float|null $a, string|int|float|null $b): string
    {
        return bcadd(self::of($a), self::of($b), self::SCALE);
    }

    /**
     * @return numeric-string
     */
    public static function sub(string|int|float|null $a, string|int|float|null $b): string
    {
        return bcsub(self::of($a), self::of($b), self::SCALE);
    }

    /**
     * Whole-number percentage of $part in $total (0 when total is zero).
     */
    public static function percent(string|int|float|null $part, string|int|float|null $total): int
    {
        $total = self::of($total);
        if (bccomp($total, '0', self::SCALE) !== 1) {
            return 0;
        }

        return (int) round((float) bcdiv(bcmul(self::of($part), '100', 4), $total, 4));
    }
}
