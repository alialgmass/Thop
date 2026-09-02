<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Traits\HasCreatedByColumn;
use Modules\Core\Support\Traits\HasUpdatedByColumn;

/**
 * Base for the controlled reference lists (governorates, fabric types,
 * materials, colors, units). Every term carries a bilingual name and a
 * stable machine slug.
 *
 * @property int $id
 * @property string $name_ar
 * @property string $name_en
 * @property string $slug
 * @property bool $is_active
 */
abstract class TaxonomyTerm extends Model
{
    use HasCreatedByColumn;
    use HasUpdatedByColumn;

    protected $guarded = ['id'];

    protected $attributes = ['is_active' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The bilingual name resolved for the active application locale, with a
     * fallback to Arabic (the platform's primary language).
     */
    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $locale === 'en' ? $this->name_en : $this->name_ar;
    }
}
