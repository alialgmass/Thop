<?php

namespace Modules\Favorites\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Favorites\Enums\FavoritableType;

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
            'type' => ['required', Rule::enum(FavoritableType::class)],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }
}
