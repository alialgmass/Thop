<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\DecideProductReview;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Http\Requests\RejectProductRequest;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Admin review of products (US-SEL-11, BR-ADM-01). The decision itself is
 * applied by {@see DecideProductReview} — shared with the Filament panel — so
 * the state guard, audit-log write (BR-ADM-01), and domain events live in one
 * place. A {@see ProductNotInReviewException} propagates and is rendered as a
 * 409 envelope (custom code 4093).
 */
class AdminProductReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductPolicy $policy,
        private readonly DecideProductReview $decide,
    ) {}

    public function queue(Request $request): JsonResponse
    {
        abort_unless($this->policy->viewAny($request->user()), 403);

        $products = Product::query()
            ->where('status', ProductStatus::PendingReview)
            ->with(['businessAccount', 'colors', 'media', 'fabricType', 'material', 'governorate'])
            ->latest()
            ->paginate();

        $payload = ProductResource::collection($products)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['products' => $payload])
            ->apiResponse();
    }

    public function approve(Request $request, Product $product): JsonResponse
    {
        abort_unless($this->policy->review($request->user(), $product), 403);

        $this->decide->approve($product, $request->user());

        return $this
            ->apiMessage('Product approved.')
            ->apiBody(['product' => new ProductResource($product->load(['businessAccount', 'colors', 'media']))])
            ->apiResponse();
    }

    public function reject(RejectProductRequest $request, Product $product): JsonResponse
    {
        abort_unless($this->policy->review($request->user(), $product), 403);

        $this->decide->reject($product, $request->user(), (string) $request->string('reason'));

        return $this
            ->apiMessage('Product rejected.')
            ->apiBody(['product' => new ProductResource($product->load(['businessAccount', 'colors', 'media']))])
            ->apiResponse();
    }

    public function hide(Request $request, Product $product): JsonResponse
    {
        abort_unless($this->policy->review($request->user(), $product), 403);

        $this->decide->hide($product, $request->user());

        return $this
            ->apiMessage('Product hidden.')
            ->apiBody(['product' => new ProductResource($product->load(['businessAccount', 'colors', 'media']))])
            ->apiResponse();
    }
}
