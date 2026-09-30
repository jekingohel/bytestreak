<?php

namespace App\Models;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeType;
use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'category_id', 'created_by', 'type', 'difficulty', 'title', 'question',
    'code_snippet', 'code_language', 'explanation', 'xp', 'status', 'publish_date',
])]
class Challenge extends Model
{
    public const CODE_LANGUAGES = ['php', 'javascript', 'sql', 'bash', 'css', 'html', 'json', 'plaintext'];

    protected function casts(): array
    {
        return [
            'type' => ChallengeType::class,
            'difficulty' => Difficulty::class,
            'status' => ChallengeStatus::class,
            'publish_date' => 'date',
            'xp' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ChallengeOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ChallengeAttempt::class);
    }

    /** Everything a developer may open: live dailies up to today plus bonus challenges. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ChallengeStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('publish_date')->orWhereDate('publish_date', '<=', today()));
    }

    /** Published challenges that were (or are) the challenge of their day. */
    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('status', ChallengeStatus::Published)->whereNotNull('publish_date');
    }

    /** Waiting in line for the next day that has nothing scheduled. */
    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', ChallengeStatus::Scheduled)->whereNull('publish_date');
    }

    public function isVisible(): bool
    {
        return $this->status === ChallengeStatus::Published
            && ($this->publish_date === null || $this->publish_date->lte(today()));
    }

    public function isDailyOn(Carbon $date): bool
    {
        return $this->status === ChallengeStatus::Published
            && $this->publish_date !== null
            && $this->publish_date->isSameDay($date);
    }

    /** XP a correct answer is worth right now: full on its own day, a share afterwards. */
    public function xpOn(Carbon $date): int
    {
        return $this->isDailyOn($date)
            ? $this->xp
            : (int) max(1, floor($this->xp * config('bytestreak.xp.practice_multiplier')));
    }

    /** Summary used by cards and lists. Never exposes the correct answer. */
    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'difficulty' => $this->difficulty->value,
            'xp' => $this->xp,
            'publish_date' => $this->publish_date?->toDateString(),
            'category' => $this->category->toChip(),
        ];
    }
}
