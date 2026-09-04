<?php

namespace Modules\Favorites\Http\Controllers;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Favorites\Http\Requests\StoreFavoriteRequest;
use Modules\Favorites\Http\Resources\FavoriteResource;
use Modules\Favorites\Models\Favorite;

/**
 * Saved products and suppliers for the current user (US-SRC-08, US-BUY-02).
 * Favorites are private — the list is always scoped to the caller and removal
 * is owner-only (BR-FAV-01, §8 matrix).
 */
class FavoriteController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $type = FavoritableType::tryFrom((string) $request->query('type'));

        $query = Favorite::query()
            ->where('user_id', $request->user()->getKey())
            ->with(['favoritable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Product::class => ['fabricType', 'material', 'governorate', 'colors', 'media', 'businessAccount'],
                BusinessAccount::class => ['governorate'],
            ])])
            ->latest();

        if ($type !== null) {
            $query->where('favoritable_type', $type->value);
        }

        $payload = FavoriteResource::collection($query->paginate())->toResponse($request)->getData(true);

        return $this->apiBody(['favorites' => $payload])->apiResponse();
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $type = FavoritableType::from($request->string('type')->toString());
        $id = $request->integer('id');

        if ($type->find($id) === null) {
            return $this->apiCode(404)->apiMessage(__('favorites::messages.target_missing'))->apiResponse();
        }

        $attributes = [
            'user_id' => $request->user()->getKey(),
            'favoritable_type' => $type->value,
            'favoritable_id' => $id,
        ];

        try {
            $favorite = Favorite::firstOrCreate($attributes);
            $created = $favorite->wasRecentlyCreated;
        } catch (QueryException) {
            // Concurrent identical POST lost the race to the unique index
            // (BR-FAV-01) — treat as already-saved, not an error.
            $favorite = Favorite::where($attributes)->firstOrFail();
            $created = false;
        }

        return $this
            ->apiCode($created ? 201 : 200)
            ->apiMessage(__('favorites::messages.saved'))
            ->apiBody(['favorite' => new FavoriteResource($favorite)])
            ->apiResponse();
    }

    public function destroy(Favorite $favorite): JsonResponse
    {
        $this->authorize('delete', $favorite);

        $favorite->delete();

        return $this->apiMessage(__('favorites::messages.removed'))->apiResponse();
    }
}
