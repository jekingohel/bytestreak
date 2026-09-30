<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'amount', 'reason', 'description', 'challenge_attempt_id', 'badge_id', 'created_at'])]
class XpEntry extends Model
{
    public const REASON_CORRECT = 'correct';

    public const REASON_PARTICIPATION = 'participation';

    public const REASON_STREAK_BONUS = 'streak_bonus';

    public const REASON_BADGE = 'badge';

    public const UPDATED_AT = null;

    protected $table = 'xp_ledger';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
