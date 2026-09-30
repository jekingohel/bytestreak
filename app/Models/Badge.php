<?php

namespace App\Models;

use App\Enums\BadgeCriteria;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'slug', 'name', 'description', 'emoji', 'criteria_type', 'criteria_value',
    'category_id', 'xp_bonus', 'sort_order', 'is_active',
])]
class Badge extends Model
{
    protected function casts(): array
    {
        return [
            'criteria_type' => BadgeCriteria::class,
            'criteria_value' => 'integer',
            'xp_bonus' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')->withPivot('earned_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function toTile(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'emoji' => $this->emoji,
            'xp_bonus' => $this->xp_bonus,
        ];
    }
}
