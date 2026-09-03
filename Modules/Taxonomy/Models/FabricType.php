<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Taxonomy\Database\Factories\FabricTypeFactory;

class FabricType extends TaxonomyTerm
{
    /** @use HasFactory<FabricTypeFactory> */
    use HasFactory;

    protected static function newFactory(): FabricTypeFactory
    {
        return FabricTypeFactory::new();
    }
}
