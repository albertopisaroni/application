<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AllowOnlyAppSubdomain;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', AllowOnlyAppSubdomain::class);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'social/google/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if (str_starts_with($request->getHost(), 'api.')) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        [
                            'message' => 'No route for that URI'
                        ]
                    ]
                ], 404);
            }
    
            // altrimenti lascia il comportamento standard (HTML)
            return null;
        });
    })->create();