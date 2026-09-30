<?php

namespace App\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function defaultXp(): int
    {
        return (int) config("bytestreak.xp.by_difficulty.{$this->value}", 10);
    }

    /** @return array<int, array{value: string, label: string, xp: int}> */
    public static function options(): array
    {
        return array_map(
            fn (self $d) => ['value' => $d->value, 'label' => $d->label(), 'xp' => $d->defaultXp()],
            self::cases(),
        );
    }
}
