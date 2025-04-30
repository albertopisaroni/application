<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OpenApiController extends Controller
{

    /**
     * Questo endpoint non verrà documentato.
     *
     * @hideFromAPIDocumentation
     */
    public function sdiCallback(Request $request)
    {
        $expectedToken = config('services.openapi.sdi.callback.token');
        $authHeader = $request->header('Authorization');

        // ✅ Validazione sicurezza
        if ($authHeader !== 'Bearer ' . $expectedToken) {
            Log::warning('⛔ Accesso non autorizzato al callback SDI', [
                'ip' => $request->ip(),
                'auth' => $authHeader,
            ]);
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // 📦 Log della chiamata SDI
        Log::info('📥 Ricevuto callback SDI', [
            'ip'       => $request->ip(),
            'method'   => $request->method(),
            'headers'  => $request->headers->all(),
            'query'    => $request->query(),
            'body'     => $request->all(),
        ]);

        return response()->json(['received' => true], Response::HTTP_OK);
    }
}