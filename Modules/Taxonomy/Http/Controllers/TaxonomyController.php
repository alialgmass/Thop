<?php

namespace Modules\Taxonomy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
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
    use ApiResponse;

    public function governorates(): JsonResponse
    {
        return $this->list('governorates', Governorate::class);
    }

    public function fabricTypes(): JsonResponse
    {
        return $this->list('fabric_types', FabricType::class);
    }

    public function materials(): JsonResponse
    {
        return $this->list('materials', Material::class);
    }

    public function colors(): JsonResponse
    {
        return $this->list('colors', Color::class);
    }

    public function units(): JsonResponse
    {
        return $this->list('units', Unit::class);
    }

    /**
     * @param  class-string<TaxonomyTerm>  $term
     */
    private function list(string $key, string $term): JsonResponse
    {
        return $this
            ->apiBody([$key => TaxonomyTermResource::collection(
                $term::query()->active()->orderBy('name_en')->get(),
            )])
            ->apiResponse();
    }
}
