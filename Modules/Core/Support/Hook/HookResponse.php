<?php

namespace Modules\Core\Support\Hook;

use Illuminate\Http\JsonResponse;

/**
 * @version 1.0
 */
trait HookResponse
{
    protected int $code = 200;

    protected int $customCode = 2000;

    protected array $body = [];

    protected array $routes = [];

    protected ?string $message = null;

    protected string $info = 'from response action';

    protected function apiResponse(): JsonResponse
    {
        return response()->json([
            'custom_code' => $this->customCode,
            'status' => $this->code === 200,
            'message' => $this->message ?? __('app.messages.data_retrieved_successfully'),
            'body' => (object) $this->body,
        ], $this->code);
    }

    protected function apiBody(array|object $body = []): static
    {
        foreach ($body as $key => $value) {
            $this->body[$key] = $value;
        }

        return $this;
    }

    protected function apiMessage(string $message = ''): static
    {
        $this->message = $message;

        return $this;
    }

    protected function apiInfo(string $info = '', $addToCurrent = false): static
    {
        $addToCurrent ? $this->info .= $info : $this->info = $info;

        return $this;
    }

    protected function apiCode(int $code): static
    {
        $this->code = $code;

        return $this;
    }

    protected function apiCustomCode(int $customCode): static
    {
        $this->customCode = $customCode;

        return $this;
    }
}
