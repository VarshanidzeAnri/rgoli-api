<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SprintState: string
{
    use HasLabel;

    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';

    public static function translationKey(): string
    {
        return 'sprint_state';
    }
}
