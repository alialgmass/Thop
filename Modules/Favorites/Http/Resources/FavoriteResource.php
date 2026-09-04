<?php

namespace Modules\Favorites\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Favorites\Enums\FavoritableType;

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
            'item' => $this->whenLoaded(
                'favoritable',
                fn () => FavoritableType::from($this->favoritable_type)->card($this->favoritable),
            ),
        ];
    }
}
