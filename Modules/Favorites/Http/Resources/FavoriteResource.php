<?php

namespace Modules\Favorites\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Http\Resources\ProductCardResource;
use Modules\Catalog\Models\Product;
use Modules\Search\Http\Resources\SupplierCardResource;

/**
 * One saved favorite plus a card view of its target, so the client can render
 * the favorites list without a second round-trip.
 */
class FavoriteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->favoritable_type,
            'favoritable_id' => $this->favoritable_id,
            'created_at' => $this->created_at,
            'item' => $this->whenLoaded('favoritable', fn () => $this->cardFor($this->favoritable)),
        ];
    }

    private function cardFor(mixed $favoritable): ?JsonResource
    {
        return match (true) {
            $favoritable instanceof Product => new ProductCardResource($favoritable),
            $favoritable instanceof BusinessAccount => new SupplierCardResource($favoritable),
            default => null,
        };
    }
}
