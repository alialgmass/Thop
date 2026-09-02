<?php

namespace Modules\Core\Exceptions\ApiException;

class ExceptionResponse extends ApiException
{
    public static function instance($message, $code = 400): static
    {
        return new static($message, $code);
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
        return $this->customCode;
    }

    public function setCustomCode(int $customCode): static
    {
        $this->customCode = $customCode;

        return $this;
    }
}
