<?php

namespace Modules\Catalog\Http\Requests;

use Modules\Catalog\Http\Requests\Concerns\ProductRules;
use Modules\Core\Http\Requests\BaseRequest;

class StoreProductRequest extends BaseRequest
{
    use ProductRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            $this->productRules(partial: false),
            ['price' => $this->pricingRules()],
        );
    }
}
