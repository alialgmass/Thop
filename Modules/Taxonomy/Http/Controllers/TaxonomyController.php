<?php

namespace Modules\Taxonomy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Taxonomy\Http\Resources\TaxonomyTermResource;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Governorate;
use Modules\Taxonomy\Models\Material;
use Modules\Taxonomy\Models\TaxonomyTerm;
use Modules\Taxonomy\Models\Unit;

/**
 * Public, read-only access to the controlled reference lists. Mutation is an
 * admin capability delivered in Phase 9 (US-ADM-03) and deliberately absent here.
 */
class TaxonomyController extends Controller
{
    public function governorates(): AnonymousResourceCollection
    {
        return $this->list(Governorate::class);
    }

    public function fabricTypes(): AnonymousResourceCollection
    {
        return $this->list(FabricType::class);
    }

    public function materials(): AnonymousResourceCollection
    {
        return $this->list(Material::class);
    }

    public function colors(): AnonymousResourceCollection
    {
        return $this->list(Color::class);
    }

    public function units(): AnonymousResourceCollection
    {
        return $this->list(Unit::class);
    }

    /**
     * @param  class-string<TaxonomyTerm>  $term
     */
    private function list(string $term): AnonymousResourceCollection
    {
        return TaxonomyTermResource::collection(
            $term::query()->active()->orderBy('name_en')->get(),
        );
    }
}
