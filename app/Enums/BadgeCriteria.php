<?php

namespace App\Enums;

enum BadgeCriteria: string
{
    case AttemptsTotal = 'attempts_total';
    case CorrectTotal = 'correct_total';
    case Streak = 'streak';
    case CategoryCorrect = 'category_correct';
    case CorrectInARow = 'correct_in_a_row';
    case Level = 'level';

    public function label(): string
    {
        return match ($this) {
            self::AttemptsTotal => 'Challenges completed',
            self::CorrectTotal => 'Correct answers',
            self::Streak => 'Day streak',
            self::CategoryCorrect => 'Correct answers in a category',
            self::CorrectInARow => 'Correct answers in a row',
            self::Level => 'Level reached',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
