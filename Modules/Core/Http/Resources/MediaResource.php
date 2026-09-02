<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Support\Media\Media;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => $this->file_name,
            'url' => $this->getUrl(),
            'size' => $this->size,
            'extension' => $this->extension,
        ];
    }
}
