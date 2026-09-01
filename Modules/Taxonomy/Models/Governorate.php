<?php

namespace Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Taxonomy\Database\Factories\GovernorateFactory;

class Governorate extends TaxonomyTerm
{
    /** @use HasFactory<GovernorateFactory> */
    use HasFactory;

    protected static function newFactory(): GovernorateFactory
    {
        return GovernorateFactory::new();
    }
}
