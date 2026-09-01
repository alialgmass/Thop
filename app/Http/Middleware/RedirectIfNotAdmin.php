<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('Admin')) {
            abort(403, 'This area requires administrator privileges.');
        }

        return $next($request);
    }
}
