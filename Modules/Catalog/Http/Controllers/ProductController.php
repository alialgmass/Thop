<?php

namespace Modules\Catalog\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\CreateProduct;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Http\Requests\StoreProductRequest;
use Modules\Catalog\Http\Requests\UpdateProductRequest;
use Modules\Catalog\Http\Requests\UpdateProductStatusRequest;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Models\Product;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Subscriptions\Services\EntitlementService;

class ProductController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    /**
     * The seller's own products (all statuses), paginated.
     * This is the seller catalog view (US-SEL-02). Public search across all
     * sellers is Phase 4; this endpoint exposes only the owner's products.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $products = Product::query()
            ->where('business_account_id', $user->businessAccount->getKey())
            ->with(['colors', 'priceTiers', 'media', 'fabricType', 'material', 'governorate'])
            ->latest()
            ->paginate();

        $payload = ProductResource::collection($products)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['products' => $payload])
            ->apiResponse();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $business = $user->businessAccount;

        $this->authorize('create', Product::class);

        $product = app(CreateProduct::class)->create(
            $business,
            $request->validated(),
            colorIds: $request->input('colors', []),
            priceTiers: $request->input('price_tiers', []),
            explicitDraft: (bool) $request->input('draft'),
        );

        return $this
            ->apiCode(201)
            ->apiMessage(__('catalog::messages.created'))
            ->apiBody(['product' => new ProductResource($product->load(['colors', 'priceTiers', 'fabricType', 'material', 'governorate']))])
            ->apiResponse();
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this
            ->apiBody(['product' => new ProductResource($product->load(['colors', 'priceTiers', 'media', 'fabricType', 'material', 'governorate']))])
            ->apiResponse();
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        if ($request->has('colors')) {
            $product->colors()->sync($request->input('colors', []));
        }

        if ($request->has('price_tiers')) {
            $product->priceTiers()->delete();
            if ($request->input('price_tiers')) {
                $product->priceTiers()->createMany($request->input('price_tiers'));
            }
        }

        return $this
            ->apiMessage(__('catalog::messages.updated'))
            ->apiBody(['product' => new ProductResource($product->load(['colors', 'priceTiers', 'fabricType', 'material', 'governorate']))])
            ->apiResponse();
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $business = $product->businessAccount;

        $product->delete();

        app(EntitlementService::class)
            ->decrementUsage($business, 'product_count');

        return $this
            ->apiMessage(__('catalog::messages.deleted'))
            ->apiResponse();
    }

    public function duplicate(Product $product): JsonResponse
    {
        $this->authorize('duplicate', $product);

        $newProduct = $product->replicate([
            'status',
            'rejection_reason',
        ])->fill([
            'status' => ProductStatus::Draft,
        ]);

        $newProduct->save();

        // Sync colors and replicate price tiers from the original
        $newProduct->colors()->sync($product->colors->pluck('id'));

        if ($product->priceTiers->count()) {
            $newProduct->priceTiers()->createMany(
                $product->priceTiers->only(['min_qty', 'unit_price'])->toArray()
            );
        }

        app(EntitlementService::class)
            ->incrementUsage($product->businessAccount, 'product_count');

        return $this
            ->apiCode(201)
            ->apiMessage(__('catalog::messages.duplicated'))
            ->apiBody(['product' => new ProductResource($newProduct->load(['colors', 'priceTiers', 'fabricType', 'material', 'governorate']))])
            ->apiResponse();
    }

    public function updateStatus(UpdateProductStatusRequest $request, Product $product): JsonResponse
    {
        $this->authorize('updateStatus', $product);

        $status = ProductStatus::from($request->input('status'));

        $product->forceFill(['status' => $status])->save();

        return $this
            ->apiMessage(__('catalog::messages.status_updated'))
            ->apiBody(['product' => new ProductResource($product)])
            ->apiResponse();
    }
}
