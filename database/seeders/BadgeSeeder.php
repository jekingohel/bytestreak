<?php

namespace Database\Seeders;

use App\Enums\BadgeCriteria;
use App\Models\Badge;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        // [slug, name, emoji, description, criteria, value, category slug, bonus XP]
        $badges = [
            ['first-commit', 'First Commit', '🌱', 'Answer your very first challenge.', BadgeCriteria::AttemptsTotal, 1, null, 10],
            ['warming-up', 'Warming Up', '🔥', 'Keep a 3-day streak going.', BadgeCriteria::Streak, 3, null, 15],
            ['week-warrior', 'Week Warrior', '⚡', 'Reach a 7-day streak.', BadgeCriteria::Streak, 7, null, 30],
            ['unstoppable', 'Unstoppable', '🚀', 'Reach a 30-day streak.', BadgeCriteria::Streak, 30, null, 100],
            ['sharp-shooter', 'Sharp Shooter', '🎯', 'Get 10 answers right.', BadgeCriteria::CorrectTotal, 10, null, 25],
            ['half-century', 'Half Century', '🏏', 'Get 50 answers right.', BadgeCriteria::CorrectTotal, 50, null, 75],
            ['perfect-week', 'Perfect Week', '💎', 'Answer 5 challenges in a row correctly.', BadgeCriteria::CorrectInARow, 5, null, 40],
            ['regular', 'Regular', '📅', 'Complete 25 challenges.', BadgeCriteria::AttemptsTotal, 25, null, 40],
            ['rising-star', 'Rising Star', '🌟', 'Reach level 5.', BadgeCriteria::Level, 5, null, 50],
            ['php-explorer', 'PHP Explorer', '🐘', 'Get 3 PHP challenges right.', BadgeCriteria::CategoryCorrect, 3, 'php', 20],
            ['laravel-explorer', 'Laravel Explorer', '🧭', 'Get 3 Laravel challenges right.', BadgeCriteria::CategoryCorrect, 3, 'laravel', 20],
            ['sql-solver', 'SQL Solver', '🗄️', 'Get 3 MySQL challenges right.', BadgeCriteria::CategoryCorrect, 3, 'mysql', 20],
            ['promise-keeper', 'Promise Keeper', '🤝', 'Get 3 JavaScript challenges right.', BadgeCriteria::CategoryCorrect, 3, 'javascript', 20],
            ['reactive-mind', 'Reactive Mind', '🎨', 'Get 3 Vue challenges right.', BadgeCriteria::CategoryCorrect, 3, 'vue', 20],
            ['branch-manager', 'Branch Manager', '🐙', 'Get 3 Git challenges right.', BadgeCriteria::CategoryCorrect, 3, 'git', 20],
            ['terminal-tamer', 'Terminal Tamer', '🐧', 'Get 3 Linux challenges right.', BadgeCriteria::CategoryCorrect, 3, 'linux', 20],
            ['pixel-perfect', 'Pixel Perfect', '🌐', 'Get 3 HTML/CSS challenges right.', BadgeCriteria::CategoryCorrect, 3, 'html-css', 20],
            ['bug-hunter', 'Bug Hunter', '🐛', 'Get 3 Debugging challenges right.', BadgeCriteria::CategoryCorrect, 3, 'debugging', 20],
        ];

        foreach ($badges as $index => [$slug, $name, $emoji, $description, $criteria, $value, $category, $bonus]) {
            Badge::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'emoji' => $emoji,
                'description' => $description,
                'criteria_type' => $criteria,
                'criteria_value' => $value,
                'category_id' => $category ? $categories[$category] : null,
                'xp_bonus' => $bonus,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
