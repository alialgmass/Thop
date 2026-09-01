<?php

namespace Modules\Admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Modules\Admin\Enums\AuditAction;
use RuntimeException;

/**
 * An immutable record of an administrative action (US-ADM-09, BR-ADM-01).
 *
 * The only supported write path is {@see AuditLog::record()}. Updates and
 * deletes are blocked at the model layer so no accidental code path — and no
 * future endpoint — can rewrite history.
 *
 * @property int $id
 * @property int $actor_id
 * @property string $action
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Audit log entries are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Audit log entries are immutable and cannot be deleted.');
        });
    }

    /**
     * Append one entry to the audit log. This is the sole write path.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(User $actor, AuditAction|string $action, Model $auditable, array $metadata = []): self
    {
        return static::create([
            'actor_id' => $actor->getKey(),
            'action' => $action instanceof AuditAction ? $action->value : $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
