<?php

namespace Modules\Core\Support\Traits\Request;

use Illuminate\Contracts\Validation\Validator;
use Modules\Core\Exceptions\ApiException\ValidationExceptionResponse;

trait ValidationRequest
{
    public function authorize()
    {
        return true;
    }

    public function attributes()
    {
        return array_merge([

        ], $this->attributesAction());
    }

    public function attributesAction(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        throw ValidationExceptionResponse::instance($validator->errors()->messages());
    }
}
