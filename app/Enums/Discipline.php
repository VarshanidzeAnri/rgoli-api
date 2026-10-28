<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Discipline: string
{
    use HasLabel;

    case Architecture = 'architecture';
    case Interior = 'interior';
    case Construction = 'construction';
    case Finance = 'finance';

    public static function translationKey(): string
    {
        return 'discipline';
    }
}
