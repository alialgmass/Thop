<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $resource
 */
class DateTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'timestamp' => $this->resource->unix(),
            'formatted' => $this->resource->translatedFormat('j F Y | g:i A'),
            'human' => $this->resource->diffForHumans(),
            'dateTime' => $this->resource->toDateTimeString(),
            'date' => $this->resource->toDateString(),
        ];
    }
}
