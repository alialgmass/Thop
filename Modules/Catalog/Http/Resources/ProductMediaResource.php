<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disk' => $this->disk,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'original_name' => $this->original_name,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            // CDN URL — the public disk is configured as S3 in production
            'url' => Storage::disk($this->disk)->url($this->path),
        ];
    }
}
