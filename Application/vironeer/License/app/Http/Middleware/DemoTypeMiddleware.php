<?php

namespace Vironeer\License\App\Http\Middleware;

use Closure;
use Config;
use Cookie;

class DemoTypeMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($request->filled('demo_type')) {
            if (in_array($request->input('demo_type'), [1, 2])) {
                Config::set('vironeer.system.license_type', $request->input('demo_type'));
                Cookie::queue('demo_type', $request->input('demo_type'), 1440);
                return redirect($request->url());
            }
        }

        if ($request->hasCookie('demo_type')) {
            Config::set('vironeer.system.license_type', $request->cookie('demo_type'));
        }

        return $next($request);
    }
}
