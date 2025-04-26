<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasCompany
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->companies()->count() === 0) {
            return redirect()->route('onboarding.company');
        }

        return $next($request);
    }
}