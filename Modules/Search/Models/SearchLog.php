<?php

namespace Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A search that returned zero results (US-SRC-11). Append-only in practice —
 * the Phase 9 admin liquidity dashboard reads these as an unmet-demand signal.
 *
 * @property string $term
 * @property string $normalized_term
 * @property int $result_count
 * @property int|null $user_id
 * @property string $context
 */
class SearchLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_count' => 'integer',
        ];
    }
}
