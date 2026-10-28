<?php

namespace App\Enums\Concerns;

trait HasLabel
{
    /**
     * Translation group inside lang/{locale}/enums.php.
     */
    abstract public static function translationKey(): string;

    public function label(): string
    {
        return __('enums.'.static::translationKey().'.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => (string) $case->value, self::cases());
    }
}
