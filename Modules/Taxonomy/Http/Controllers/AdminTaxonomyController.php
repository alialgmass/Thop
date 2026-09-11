<?php

namespace Modules\Taxonomy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Controllers\AuditLogController;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Taxonomy\Actions\ManageTaxonomyTerm;
use Modules\Taxonomy\Enums\ManagedTaxonomyType;
use Modules\Taxonomy\Http\Requests\StoreTaxonomyTermRequest;
use Modules\Taxonomy\Http\Requests\UpdateTaxonomyTermRequest;
use Modules\Taxonomy\Http\Resources\AdminTaxonomyTermResource;
use Modules\Taxonomy\Models\TaxonomyTerm;

/**
 * Admin CRUD (no delete — see {@see ManageTaxonomyTerm}) over the controlled
 * reference lists (US-ADM-03, Phase 9 · T3). The gate is the `admin` route
 * middleware, matching {@see AuditLogController}'s
 * convention. `{type}` is constrained by the route pattern to
 * {@see ManagedTaxonomyType}'s values, so an unknown type 404s before it
 * reaches here.
 */
class AdminTaxonomyController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ManageTaxonomyTerm $manage) {}

    public function index(ManagedTaxonomyType $type): JsonResponse
    {
        $terms = $type->modelClass()::query()->orderBy('name_en')->get();

        return $this
            ->apiBody(['terms' => AdminTaxonomyTermResource::collection($terms)])
            ->apiResponse();
    }

    public function store(StoreTaxonomyTermRequest $request, ManagedTaxonomyType $type): JsonResponse
    {
        $term = $this->manage->create($type->modelClass(), $request->validated(), $request->user());

        return $this
            ->apiMessage('Taxonomy term created.')
            ->apiBody(['term' => new AdminTaxonomyTermResource($term)])
            ->apiResponse();
    }

    public function update(UpdateTaxonomyTermRequest $request, ManagedTaxonomyType $type, int $term): JsonResponse
    {
        // Not implicit route-model binding: $term's model class depends on
        // $type, which Laravel can't resolve until both route parameters are
        // already in hand — so it's looked up manually here instead.
        /** @var TaxonomyTerm $record */
        $record = $type->modelClass()::query()->findOrFail($term);

        $updated = $this->manage->update($record, $request->validated(), $request->user());

        return $this
            ->apiMessage('Taxonomy term updated.')
            ->apiBody(['term' => new AdminTaxonomyTermResource($updated)])
            ->apiResponse();
    }
}
