<?php

namespace Modules\Core\Support\Traits\Request;

use Illuminate\Support\Facades\Hash;

trait HasPasswordInRequest
{
    protected function prepareForValidation()
    {
        $password = Hash::needsRehash($this->password) ? Hash::make($this->password) : $this->password;
        $this->merge([
            'password' => $password,
            'password_confirmation' => $password,
        ]);

    }
}
