<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Http\Requests\ReorderProductMediaRequest;
use Modules\Catalog\Http\Requests\StoreProductMediaRequest;
use Modules\Catalog\Http\Resources\ProductMediaResource;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductMedia;
use Modules\Catalog\Policies\ProductPolicy;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Images on a seller's own product (US-SEL-03). The product always exists before
 * any media is attached (upload is a separate call), so a failed upload can
 * never lose product data — AVL-NFR-03 is satisfied by the API shape. Ownership
 * reuses {@see ProductPolicy::update}.
 */
class ProductMediaController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function store(StoreProductMediaRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $max = (int) config('catalog.media.max_per_product');

        if ($product->media()->count() >= $max) {
            throw ExceptionResponse::instance(__('catalog::messages.media_limit_reached', ['max' => $max]), 422)
                ->setCustomBody(['file' => [__('catalog::messages.media_limit_reached', ['max' => $max])]]);
        }

        $disk = (string) config('catalog.media.disk');
        $file = $request->file('file');

        $path = $file->store('products/'.$product->getKey(), $disk);

        if ($path === false) {
            throw ExceptionResponse::instance(__('catalog::messages.media_upload_failed'), 500);
        }

        $media = $product->media()->create([
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'type' => 'image',
            'sort_order' => (int) $product->media()->max('sort_order') + 1,
        ]);

        return $this
            ->apiCode(201)
            ->apiMessage(__('catalog::messages.media_uploaded'))
            ->apiBody(['media' => new ProductMediaResource($media)])
            ->apiResponse();
    }

    public function reorder(ReorderProductMediaRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $ordered = $request->orderedMediaIds();
        $owned = $product->media()->pluck('id')->all();

        sort($owned);
        $check = $ordered;
        sort($check);

        if ($check !== $owned) {
            throw ExceptionResponse::instance(__('catalog::messages.media_reorder_mismatch'), 422)
                ->setCustomBody(['media' => [__('catalog::messages.media_reorder_mismatch')]]);
        }

        foreach ($ordered as $position => $id) {
            ProductMedia::query()->whereKey($id)->update(['sort_order' => $position]);
        }

        return $this
            ->apiMessage(__('catalog::messages.media_reordered'))
            ->apiBody(['media' => ProductMediaResource::collection($product->media()->get())])
            ->apiResponse();
    }

    public function destroy(Product $product, ProductMedia $media): JsonResponse
    {
        $this->authorize('update', $product);

        abort_unless($media->product_id === $product->getKey(), 404);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return $this->apiMessage(__('catalog::messages.media_deleted'))->apiResponse();
    }
}
