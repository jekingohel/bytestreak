<?php

namespace App\Enums;

enum ChallengeType: string
{
    case MultipleChoice = 'multiple_choice';
    case PredictOutput = 'predict_output';
    case FindBug = 'find_bug';
    case Sql = 'sql';
    case TrueFalse = 'true_false';
    case WhatWouldYouUse = 'what_would_you_use';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple Choice',
            self::PredictOutput => 'Predict the Output',
            self::FindBug => 'Find the Bug',
            self::Sql => 'SQL Challenge',
            self::TrueFalse => 'True / False',
            self::WhatWouldYouUse => 'What Would You Use?',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $t) => ['value' => $t->value, 'label' => $t->label()], self::cases());
    }
}
