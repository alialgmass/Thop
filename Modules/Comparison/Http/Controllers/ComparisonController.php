<?php

namespace Modules\Comparison\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Comparison\Http\Requests\CompareRequest;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Search\Http\Resources\ProductDetailResource;
use Modules\Search\Http\Resources\SupplierCardResource;

/**
 * On-demand side-by-side comparison of up to 4 products or suppliers
 * (US-SRC-09, US-BUY-05). Nothing is persisted — the client holds the
 * selection and this endpoint just resolves it (§10.4). Only buyer-visible
 * targets are returned; unknown ids are reported back, not errored on. A
 * product with no numeric price still appears (price_on_contact) so the
 * comparison degrades gracefully (§19 risk note).
 */
class ComparisonController extends Controller
{
    use ApiResponse;

    public function show(CompareRequest $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $ids = $request->ids();

        [$items, $found] = $type === 'product'
            ? $this->products($ids)
            : $this->suppliers($ids);

        return $this
            ->apiBody([
                'type' => $type,
                'items' => $items,
                'missing_ids' => array_values(array_diff($ids, $found)),
            ])
            ->apiResponse();
    }

    /**
     * @param  list<int>  $ids
     * @return array{0: AnonymousResourceCollection, 1: list<int>}
     */
    private function products(array $ids): array
    {
        $products = Product::query()
            ->buyerVisible()
            ->whereIn('id', $ids)
            ->with(['fabricType', 'material', 'governorate', 'colors', 'media', 'priceTiers', 'businessAccount.governorate'])
            ->get();

        return [ProductDetailResource::collection($products), $products->pluck('id')->all()];
    }

    /**
     * @param  list<int>  $ids
     * @return array{0: AnonymousResourceCollection, 1: list<int>}
     */
    private function suppliers(array $ids): array
    {
        $suppliers = BusinessAccount::query()
            ->whereIn('id', $ids)
            ->activeAccount()
            ->with('governorate')
            ->get();

        return [SupplierCardResource::collection($suppliers), $suppliers->pluck('id')->all()];
    }
}
