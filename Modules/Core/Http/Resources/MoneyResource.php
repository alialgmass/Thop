<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Enums\CurrencyEnum;

class MoneyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'amount' => $this->resource->getAmount()->toFloat(),
            'currency' => CurrencyEnum::fromMoney($this->resource)->toResponse(),
            'formatted' => CurrencyEnum::format($this->resource),
        ];
    }
}
