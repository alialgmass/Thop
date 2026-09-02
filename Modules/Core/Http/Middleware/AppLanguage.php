<?php

namespace Modules\Core\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Number;

class AppLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $requestedLanguage = $request->header('Accept-Language');
        $supportedLanguages = config('app.supported-languages', []);

        if ($requestedLanguage && in_array($requestedLanguage, $supportedLanguages)) {
            app()->setLocale($requestedLanguage);
            setlocale(LC_TIME, "{$requestedLanguage}.utf8");
            Carbon::setLocale($requestedLanguage);
            Number::useLocale($requestedLanguage);
        }

        return $next($request);
    }
}
