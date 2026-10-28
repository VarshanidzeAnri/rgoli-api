<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Priority: string
{
    use HasLabel;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function translationKey(): string
    {
        return 'priority';
    }
}
