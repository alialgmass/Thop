<?php

namespace Modules\Search\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Search\Actions\ManageFeaturedPlacement;
use Modules\Search\Http\Requests\StoreFeaturedPlacementRequest;
use Modules\Search\Models\FeaturedPlacement;
use Modules\Taxonomy\Http\Controllers\AdminTaxonomyController;

/**
 * Admin CRUD over featured placements (US-SRC-10, BR-SRC-01, Phase 9 · T5).
 * The gate is the `admin` route middleware, matching
 * {@see AdminTaxonomyController}'s convention.
 */
class AdminFeaturedPlacementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ManageFeaturedPlacement $manage) {}

    public function index(): JsonResponse
    {
        $placements = FeaturedPlacement::query()->with('creator')->latest()->get();

        return $this
            ->apiBody(['placements' => $placements->map(fn (FeaturedPlacement $p): array => $this->toArray($p))])
            ->apiResponse();
    }

    public function store(StoreFeaturedPlacementRequest $request): JsonResponse
    {
        $type = FavoritableType::from($request->string('type')->toString());
        $featurableId = $request->integer('featurable_id');

        abort_if($type->find($featurableId) === null, 404, __('search::messages.featurable_not_found'));

        $placement = $this->manage->create([
            'type' => $type,
            'featurable_id' => $featurableId,
            'slot' => (string) $request->string('slot'),
            'starts_at' => $request->date('starts_at'),
            'ends_at' => $request->date('ends_at'),
        ], $request->user());

        return $this
            ->apiMessage('Featured placement created.')
            ->apiBody(['placement' => $this->toArray($placement)])
            ->apiResponse();
    }

    public function destroy(FeaturedPlacement $placement, Request $request): JsonResponse
    {
        $this->manage->remove($placement, $request->user());

        return $this
            ->apiMessage('Featured placement removed.')
            ->apiResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(FeaturedPlacement $placement): array
    {
        return [
            'id' => $placement->id,
            'type' => $placement->featurable_type,
            'featurable_id' => $placement->featurable_id,
            'slot' => $placement->slot,
            'starts_at' => $placement->starts_at,
            'ends_at' => $placement->ends_at,
            'created_by' => $placement->created_by,
        ];
    }
}
