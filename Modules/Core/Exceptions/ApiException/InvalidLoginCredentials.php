<?php

namespace Modules\Core\Exceptions\ApiException;

class InvalidLoginCredentials extends ApiException
{
    public function getCustomCode(): int
    {
        return 1001;
    }

    public function getCustomMessage(): ?string
    {
        return __('exceptions.invalid-login-credentials');
    }
}
