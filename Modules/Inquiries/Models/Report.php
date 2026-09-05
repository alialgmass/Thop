<?php

namespace Modules\Inquiries\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Inquiries\Database\Factories\ReportFactory;

/**
 * Either party flagging an inquiry as abusive (US-INQ-09). This is a durable
 * record for the Admin dispute queue (Phase 9, US-ADM-08) to read — no
 * moderation workflow lives here.
 *
 * @property int $id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property int $reporter_id
 * @property string $reason
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
