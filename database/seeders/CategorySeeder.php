<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['php', 'PHP', '🐘', '#8892F6', 'Language concepts, syntax, types, arrays, OOP'],
            ['laravel', 'Laravel', '🔥', '#FF5A4F', 'Eloquent, routing, middleware, validation, queues, events'],
            ['mysql', 'MySQL', '🗄️', '#3BA7E5', 'Queries, joins, indexes, aggregation, database concepts'],
            ['javascript', 'JavaScript', '🟨', '#EAB308', 'Variables, promises, async code, arrays, browser concepts'],
            ['vue', 'Vue', '🎨', '#34D399', 'Components, props, events, reactivity, Composition API'],
            ['git', 'Git', '🐙', '#F97316', 'Branches, merge/rebase, cherry-pick, reset, troubleshooting'],
            ['linux', 'Linux', '🐧', '#A3A3B5', 'Commands, permissions, processes, logs, environment basics'],
            ['html-css', 'HTML/CSS', '🌐', '#EC4899', 'Layout, selectors, forms, accessibility, browser behavior'],
            ['debugging', 'Debugging', '🐛', '#84CC16', 'Find the bug, identify the cause, choose the correct fix'],
            ['general', 'General Programming', '🧠', '#C084FC', 'Algorithms, clean code, logic, common engineering concepts'],
        ];

        foreach ($categories as $index => [$slug, $name, $emoji, $color, $description]) {
            Category::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'emoji' => $emoji,
                'color' => $color,
                'description' => $description,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
