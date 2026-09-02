<?php

namespace Modules\Core\Exceptions\ApiException;

class ValidationExceptionResponse extends ApiException
{
    public static function instance(array $errors): static
    {
        return (new static(collect($errors)->first()[0]))->setCustomBody($errors);
    }

    public function getCustomMessage(): ?string
    {
        return $this->message;
    }

    public function getCustomBody(): ?array
    {
        return $this->customBody;
    }

    public function getCustomCode(): int
    {
        return 4000;
    }
}
