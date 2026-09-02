<?php

namespace Modules\Core\Http\Middleware;

use Closure;

class TimezoneConfig
{
    public function handle($request, Closure $next)
    {
        $timezone = $request->headers->get('Accept-Timezone');

        if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
