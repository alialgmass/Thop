<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base for the controlled reference lists (governorates, fabric types,
 * materials, colors, units). Every term carries a bilingual name and a
 * stable machine slug.
 *
 * @property int $id
 * @property string $name_ar
 * @property string $name_en
 * @property string $slug
 */
abstract class TaxonomyTerm extends Model
{
    protected $guarded = ['id'];

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
