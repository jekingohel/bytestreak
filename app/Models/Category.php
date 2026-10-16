<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'emoji', 'color', 'description', 'sort_order', 'is_active'])]
class Category extends Model
{
    use HasUniqueSlug;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function toChip(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'emoji' => $this->emoji,
            'color' => $this->color,
        ];
    }

    /** A row in the admin list. Expects withCount('challenges'). */
    public function toAdminRow(): array
    {
        return [
            ...$this->toChip(),
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'challenges' => $this->challenges_count,
        ];
    }
}
