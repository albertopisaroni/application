<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiToken;

class ApiTokenMiddleware
{
    public function handle($request, Closure $next)
    {
        $providedToken = $request->bearerToken();

        if (!$providedToken) {
            return response()->json([
                'success' => false,
                'errors' => [
                    ['message' => 'No token provided']
                ]
            ], 401);
        }

        

        $apiToken = ApiToken::where('token', hash('sha256', $providedToken))->first();

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'errors' => [
                    ['message' => 'The provided token is invalid']
                ]
            ], 403);
        }

        // Salva la company associata per l'uso successivo
        $request->merge(['company' => $apiToken->company]);

        return $next($request);
    }
}