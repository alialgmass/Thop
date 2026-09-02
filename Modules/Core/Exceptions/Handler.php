<?php

namespace Modules\Core\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\ApiException\ApiException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler
{
    /**
     * Render an exception. Returns null when the framework's default handling
     * should take over (web requests, unhandled exception types).
     */
    public function render(Throwable $e, Request $request): mixed
    {
        if (! $request->expectsJson()) {
            return null;
        }

        if ($e instanceof ApiException) {
            return $e->toResponse();
        }

        return null;
    }

    public function unauthenticated(
        AuthenticationException $exception,
        Request $request
    ): JsonResponse|Response {
        return $request->expectsJson()
            ? response()->json([
                'custom_code' => 4001,
                'status' => false,
                'message' => __('app.messages.please-log-in-first'),
                'body' => [],
                'info' => 'from unauthenticated response in Handler',
            ], 401)
            : redirect()->guest(route('filament.admin.auth.login'));
    }
}
