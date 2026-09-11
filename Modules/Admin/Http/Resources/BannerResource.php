<?php

namespace Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admin\Models\Banner;

/**
 * Public shape for `GET /api/v1/banners` — the separate marketplace client's
 * homepage. Only what a client rendering a banner carousel needs.
 *
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->imageUrl(),
            'link_url' => $this->link_url,
            'position' => $this->position,
        ];
    }
}
