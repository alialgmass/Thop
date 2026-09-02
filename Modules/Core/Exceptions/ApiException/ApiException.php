<?php

namespace Modules\Core\Exceptions\ApiException;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class ApiException extends Exception
{
    protected $code = 400;

    protected int $customCode = 4000;

    protected array $customBody = [];

    abstract public function getCustomMessage(): ?string;

    public function getCustomBody(): ?array
    {
        return [];
    }

    abstract public function getCustomCode(): int;

    public function setCustomCode(int $customCode): static
    {
        $this->customCode = $customCode;

        return $this;
    }

    public function setCustomBody(array $errors): static
    {
        $this->customBody = array_merge($this->customBody, $errors);

        return $this;
    }

    public function toResponse(): JsonResponse
    {

        return response()->json([
            'custom_code' => $this->getCustomCode(),
            'status' => false,
            'message' => $this->getCustomMessage(),
            'body' => $this->getCustomBody(),
            'info' => 'from ApiException response',
        ], $this->code);

    }
}
