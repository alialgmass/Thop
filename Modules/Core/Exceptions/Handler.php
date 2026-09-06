<?php

namespace Modules\Core\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\ApiException\ApiException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every API failure through the Core envelope
 * (`{ custom_code, status, message, body, info }`, spec §11). Authorization
 * denials (403) and missing resources (404) are enveloped here too, so no
 * `/api/*` response falls back to Laravel's native `{ "message": "..." }` shape
 * — including endpoints (e.g. the signed document download) whose client does
 * not send `Accept: application/json`.
 */
class Handler
{
    /** custom_code for an enveloped 403. */
    public const FORBIDDEN_CODE = 4031;

    /** custom_code for an enveloped 404. */
    public const NOT_FOUND_CODE = 4040;

    /**
     * Render an exception. Returns null when the framework's default handling
     * should take over (web/panel requests, unhandled exception types).
     */
    public function render(Throwable $e, Request $request): mixed
    {
        if (! $this->wantsEnvelope($request)) {
            return null;
        }

        if ($e instanceof ApiException) {
            return $e->toResponse();
        }

        if ($this->isForbidden($e)) {
            return $this->envelope(self::FORBIDDEN_CODE, $this->deliberateMessage($e) ?? __('exception.unauthorized'), 403);
        }

        if ($this->isNotFound($e)) {
            return $this->envelope(self::NOT_FOUND_CODE, $this->deliberateMessage($e) ?? __('exceptions.not_found'), 404);
        }

        return null;
    }

    public function unauthenticated(
        AuthenticationException $exception,
        Request $request
    ): JsonResponse|Response {
        return $this->wantsEnvelope($request)
            ? response()->json([
                'custom_code' => 4001,
                'status' => false,
                'message' => __('app.messages.please-log-in-first'),
                'body' => [],
                'info' => 'from unauthenticated response in Handler',
            ], 401)
            : redirect()->guest(route('filament.admin.auth.login'));
    }

    /**
     * An API client either asks for JSON explicitly or hits an `/api/*` route
     * (covers the binary download endpoints that omit the Accept header).
     */
    private function wantsEnvelope(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }

    private function isForbidden(Throwable $e): bool
    {
        return $e instanceof AuthorizationException
            || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403);
    }

    private function isNotFound(Throwable $e): bool
    {
        return $e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException;
    }

    /**
     * The message from a deliberate `abort(403, '…')` / `abort(404, '…')`, or
     * null for framework-generated exceptions (whose messages leak internals or
     * aren't localized — those fall back to our own localized copy).
     *
     * Laravel wraps `AuthorizationException` / `ModelNotFoundException` in an
     * `HttpException` before this handler runs, keeping the original as the
     * previous exception — so the original type is what tells them apart.
     */
    private function deliberateMessage(Throwable $e): ?string
    {
        $original = $e->getPrevious() ?? $e;

        if ($original instanceof AuthorizationException || $original instanceof ModelNotFoundException) {
            return null;
        }

        if (! $e instanceof HttpExceptionInterface) {
            return null;
        }

        $message = trim($e->getMessage());

        return $message === '' ? null : $message;
    }

    private function envelope(int $customCode, string $message, int $status): JsonResponse
    {
        return response()->json([
            'custom_code' => $customCode,
            'status' => false,
            'message' => $message,
            'body' => [],
            'info' => 'from Handler',
        ], $status);
    }
}
