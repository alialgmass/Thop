<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Taxonomy\Database\Factories\MaterialFactory;

class Material extends TaxonomyTerm
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    protected static function newFactory(): MaterialFactory
    {
        return MaterialFactory::new();
    }
}
