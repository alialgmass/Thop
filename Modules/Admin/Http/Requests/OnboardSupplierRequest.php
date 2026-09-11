<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Http\Requests\Concerns\NormalizesPhone;
use Modules\Auth\Rules\EgyptianMobile;
use Modules\Businesses\Http\Requests\Concerns\BusinessProfileRules;
use Modules\Core\Http\Requests\BaseRequest;

/**
 * Same phone rule as public registration ({@see EgyptianMobile}) and the
 * same business-profile rules the self-service flow uses
 * ({@see BusinessProfileRules}) — nothing here is a parallel validation
 * ruleset (Phase 9 · T10).
 */
class OnboardSupplierRequest extends BaseRequest
{
    use BusinessProfileRules;
    use NormalizesPhone;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new EgyptianMobile],
            'account_type' => ['required', Rule::in(['importer', 'wholesaler', 'retailer'])],
            'password' => ['required', 'string', 'confirmed', Password::default()],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'language' => ['nullable', 'in:ar,en'],
            ...$this->profileRules(partial: false),
        ];
    }
}
