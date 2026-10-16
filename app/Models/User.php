<?php

namespace App\Models;

use App\Services\Level;
use App\Services\StreakService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'show_on_leaderboard'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Mirror the column defaults so a just-created model is usable without a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_admin' => false,
        'show_on_leaderboard' => true,
        'xp' => 0,
        'current_streak' => 0,
        'longest_streak' => 0,
        'streak_freezes' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'show_on_leaderboard' => 'boolean',
            'last_completed_on' => 'date',
            'xp' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'streak_freezes' => 'integer',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ChallengeAttempt::class);
    }

    public function xpEntries(): HasMany
    {
        return $this->hasMany(XpEntry::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withPivot('earned_at');
    }

    /** @return array{level: int, title: string, xp: int, floor: int, ceiling: int, into: int, needed: int, percent: int} */
    public function levelInfo(): array
    {
        return Level::info($this->xp);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    /** Stable per-user hue so every avatar keeps its own colour. */
    public function avatarHue(): int
    {
        return ($this->id * 47) % 360;
    }

    /** The compact identity every list (leaderboard, admin tables, nav) renders. */
    public function toIdentity(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials(),
            'hue' => $this->avatarHue(),
        ];
    }

    /** A row in the admin list. Expects withCount(['attempts', 'attempts as correct_count' => …]). */
    public function toAdminRow(): array
    {
        return [
            ...$this->toIdentity(),
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'xp' => $this->xp,
            'level' => Level::forXp($this->xp),
            'streak' => app(StreakService::class)->current($this),
            'attempts' => $this->attempts_count,
            'accuracy' => $this->attempts_count > 0 ? (int) round($this->correct_count / $this->attempts_count * 100) : null,
            'last_completed_on' => $this->last_completed_on?->toDateString(),
            'joined' => $this->created_at->toDateString(),
        ];
    }
}
