<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Taxonomy\Database\Factories\ColorFactory;

/**
 * @property string|null $hex
 */
class Color extends TaxonomyTerm
{
    /** @use HasFactory<ColorFactory> */
    use HasFactory;

    protected static function newFactory(): ColorFactory
    {
        return ColorFactory::new();
    }
}
