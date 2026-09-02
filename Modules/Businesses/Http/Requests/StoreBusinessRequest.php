<?php

namespace Modules\Businesses\Http\Requests;

use Modules\Businesses\Http\Requests\Concerns\BusinessProfileRules;
use Modules\Core\Http\Requests\BaseRequest;

class StoreBusinessRequest extends BaseRequest
{
    use BusinessProfileRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->profileRules(partial: false);
    }
}
