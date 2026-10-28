<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Status: string
{
    use HasLabel;

    case Todo = 'todo';
    case Progress = 'progress';
    case Review = 'review';
    case Blocked = 'blocked';
    case Done = 'done';

    public static function translationKey(): string
    {
        return 'status';
    }

    public function isOpen(): bool
    {
        return $this !== self::Done;
    }
}
