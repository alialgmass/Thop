<?php

namespace Modules\Taxonomy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Taxonomy\Http\Resources\TaxonomyTermResource;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;
use Modules\Taxonomy\Models\Unit;

/**
 * Public, read-only access to the controlled reference lists. Mutation is an
 * admin capability delivered in Phase 9 (US-ADM-03) and deliberately absent here.
 */
class TaxonomyController extends Controller
{
    public function governorates(): AnonymousResourceCollection
    {
        return TaxonomyTermResource::collection(Governorate::query()->orderBy('name_en')->get());
    }

    public function fabricTypes(): AnonymousResourceCollection
    {
        return TaxonomyTermResource::collection(FabricType::query()->orderBy('name_en')->get());
    }

    public function materials(): AnonymousResourceCollection
    {
        return TaxonomyTermResource::collection(Material::query()->orderBy('name_en')->get());
    }

    public function colors(): AnonymousResourceCollection
    {
        return TaxonomyTermResource::collection(Color::query()->orderBy('name_en')->get());
    }

    public function units(): AnonymousResourceCollection
    {
        return TaxonomyTermResource::collection(Unit::query()->orderBy('name_en')->get());
    }
}
