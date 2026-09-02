<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Auth\Enums\AccountType;
use Modules\Core\Http\Requests\BaseRequest;

class AccountTypeRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_type' => ['required', Rule::enum(AccountType::class)],
        ];
    }

    public function accountType(): AccountType
    {
        return AccountType::from($this->input('account_type'));
    }
}
