<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /** A slug for $name that no other row uses yet: "php", then "php-2", "php-3" … */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: Str::snake(class_basename(static::class));
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
