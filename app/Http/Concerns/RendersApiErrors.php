<?php

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Emits a single-message error in the standard THOB API envelope (spec
 * Section 11): `{ "message": ..., "errors": { "<field>": [...] } }`. Shared by
 * every module's API controllers (spec Section 9.3 — response conventions are
 * shared infrastructure).
 */
trait RendersApiErrors
{
    protected function apiError(string $message, string $field, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [$field => [$message]],
        ], $status);
    }
}
