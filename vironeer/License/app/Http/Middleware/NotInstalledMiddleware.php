<?php

namespace Vironeer\License\App\Http\Middleware;

use Closure;

class NotInstalledMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!config('vironeer.install.complete')) {
            return redirect()->route('install.index');
        }
        return $next($request);
    }
}
