<?php

namespace Tests\Unit;

use App\Services\Level;
use PHPUnit\Framework\TestCase;

class LevelTest extends TestCase
{
    public function test_levels_start_at_one_and_follow_the_threshold_curve(): void
    {
        $this->assertSame(1, Level::forXp(0));
        $this->assertSame(1, Level::forXp(49));
        $this->assertSame(2, Level::forXp(50));
        $this->assertSame(3, Level::forXp(150));
        $this->assertSame(7, Level::forXp(1240));
        $this->assertSame(8, Level::forXp(1400));
    }

    public function test_every_threshold_is_the_first_xp_of_its_level(): void
    {
        for ($level = 2; $level <= 60; $level++) {
            $this->assertSame($level, Level::forXp(Level::threshold($level)));
            $this->assertSame($level - 1, Level::forXp(Level::threshold($level) - 1));
        }
    }
}
