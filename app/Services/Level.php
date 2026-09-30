<?php

namespace App\Services;

/**
 * Pure XP → level maths. Total XP needed to reach level L is 25 * (L - 1) * L,
 * so early levels come quickly and later ones take progressively longer.
 */
final class Level
{
    public static function threshold(int $level): int
    {
        return 25 * ($level - 1) * $level;
    }

    public static function forXp(int $xp): int
    {
        $level = max(1, (int) floor((1 + sqrt(1 + 4 * max(0, $xp) / 25)) / 2));

        // Guard the float maths at exact thresholds.
        while (self::threshold($level + 1) <= $xp) {
            $level++;
        }
        while ($level > 1 && self::threshold($level) > $xp) {
            $level--;
        }

        return $level;
    }

    public static function title(int $level): string
    {
        $titles = config('bytestreak.level_titles', []);

        return $titles[$level] ?? (end($titles) ?: 'Developer');
    }

    /** @return array{level: int, title: string, xp: int, floor: int, ceiling: int, into: int, needed: int, percent: int} */
    public static function info(int $xp): array
    {
        $level = self::forXp($xp);
        $floor = self::threshold($level);
        $ceiling = self::threshold($level + 1);

        return [
            'level' => $level,
            'title' => self::title($level),
            'xp' => $xp,
            'floor' => $floor,
            'ceiling' => $ceiling,
            'into' => $xp - $floor,
            'needed' => $ceiling - $xp,
            'percent' => (int) floor(($xp - $floor) / max(1, $ceiling - $floor) * 100),
        ];
    }
}
