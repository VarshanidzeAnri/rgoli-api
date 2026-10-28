<?php

namespace App\Enums;

enum Locale: string
{
    case Ka = 'ka';
    case En = 'en';
    case Ru = 'ru';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
