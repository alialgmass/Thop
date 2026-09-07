<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single authorization gate for every administrative surface — the
 * `/api/v1/admin/*` REST group (aliased `admin`) and the session-authenticated
 * Filament document routes alike (US-ADM, BR-ADM-01). Back-office capabilities
 * can never be reached by a seller or buyer, and no future admin route can
 * forget the check.
 *
 * Authentication is a separate concern handled earlier in the stack
 * (`auth:sanctum` for the API, `auth` for the panel); an unauthenticated
 * request is rejected there before it reaches this middleware.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) $request->user()?->hasRole('admin'), 403, __('exception.unauthorized'));

        return $next($request);
    }
}
