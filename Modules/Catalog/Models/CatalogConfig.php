<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A DB-backed key/value override for catalog gating flags (BR-SEL-02). Read via
 * {@see \Modules\Catalog\Support\CatalogConfig}; write path is Phase 9.
 *
 * @property int $id
 * @property string $key
 * @property string $value
 */
class CatalogConfig extends Model
{
    protected $table = 'catalog_config';

    protected $guarded = ['id'];
}
