<?php

namespace Modules\Favorites\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Favorites\Support\Favoritable;

/**
 * `POST /api/v1/favorites` — save a product or supplier (US-SRC-08, US-BUY-02).
 */
class StoreFavoriteRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Favoritable::types())],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }
}
