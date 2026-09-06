<?php

namespace Modules\Catalog\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `PATCH /api/v1/products/{product}/media/order` — the full, ordered list of the
 * product's media ids. Index 0 becomes the primary/cover image. Any id that
 * doesn't belong to the product, or a partial list, is rejected in the controller.
 */
class ReorderProductMediaRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'media' => ['required', 'array', 'min:1'],
            'media.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedMediaIds(): array
    {
        return array_map('intval', $this->input('media', []));
    }
}
