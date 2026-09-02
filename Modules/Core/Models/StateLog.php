<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Database\Factories\StateLogFactory;

/**
 * @property int $id
 * @property string $resource_type
 * @property int $resource_id
 * @property string|null $old_state
 * @property string|null $new_state
 * @property string|null $comment
 */
class StateLog extends Model
{
    /** @use HasFactory<StateLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function newFactory(): StateLogFactory
    {
        return StateLogFactory::new();
    }
}
