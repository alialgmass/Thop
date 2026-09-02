<?php

namespace Modules\Core\Exceptions\ApiException;

class NotFoundException extends ApiException
{
    protected $code = 404;

    protected int $customCode = 1004;

    public function getCustomCode(): int
    {
        return $this->customCode;
    }

    public function getCustomMessage(): ?string
    {
        return __('exceptions.not_found');
    }
}
