<?php

namespace Modules\Auth\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait RendersApiErrors
{
    /**
     * A single-message error in the standard THOB envelope (spec Section 11).
     */
    protected function apiError(string $message, string $field, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [$field => [$message]],
        ], $status);
    }
}
