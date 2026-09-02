<?php

namespace Modules\Verification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Traits\HasCreatedByColumn;
use Modules\Core\Support\Traits\HasUpdatedByColumn;
use Modules\Verification\Database\Factories\DocumentTypeFactory;

/**
 * @property int $id
 * @property string $name_ar
 * @property string $name_en
 * @property string $slug
 * @property bool $is_required
 * @property bool $is_active
 */
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasCreatedByColumn;

    use HasFactory;
    use HasUpdatedByColumn;

    protected $guarded = ['id'];

    protected $attributes = ['is_required' => true, 'is_active' => true];

    protected static function newFactory(): DocumentTypeFactory
    {
        return DocumentTypeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_active' => 'boolean'];
    }
}
