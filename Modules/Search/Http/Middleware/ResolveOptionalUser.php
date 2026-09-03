<?php

namespace Modules\Search\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public search endpoints accept an optional bearer token: a guest is fine,
 * but when a buyer is signed in we want their id for zero-result attribution
 * (US-SRC-11). Resolves the Sanctum user if a valid token is present and never
 * rejects the request otherwise.
 */
class ResolveOptionalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== null) {
            $user = auth('sanctum')->user();

            if ($user !== null) {
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }
}
