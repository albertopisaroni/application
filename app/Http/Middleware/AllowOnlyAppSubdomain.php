<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowOnlyAppSubdomain
{
    public function handle(Request $request, Closure $next)
    {
        if (!str_starts_with($request->getHost(), 'app.')) {
            abort(404); // o 403, come vuoi
        }

        return $next($request);
    }
}