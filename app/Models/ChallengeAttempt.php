<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'challenge_id', 'challenge_option_id', 'is_correct', 'is_daily', 'xp_awarded', 'submitted_at'])]
class ChallengeAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'is_daily' => 'boolean',
            'xp_awarded' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ChallengeOption::class, 'challenge_option_id');
    }
}
