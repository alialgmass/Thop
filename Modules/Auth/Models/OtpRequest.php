<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Auth\Enums\OtpPurpose;

/**
 * @property int $id
 * @property string $phone
 * @property string $code_hash
 * @property OtpPurpose $purpose
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon|null $consumed_at
 */
class OtpRequest extends Model
{
    protected $fillable = [
        'phone',
        'code_hash',
        'purpose',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasAttemptsLeft(int $max): bool
    {
        return $this->attempts < $max;
    }

    public function isUsable(int $maxAttempts): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired() && $this->hasAttemptsLeft($maxAttempts);
    }
}
