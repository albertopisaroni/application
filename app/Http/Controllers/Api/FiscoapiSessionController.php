<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FiscoApiService;
use App\Models\FiscoapiSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class FiscoapiSessionController extends Controller
{
    /**
     * Avvia una nuova sessione FiscoApi.
     */
    public function store(Request $request, FiscoApiService $fiscoApi)
    {
        $request->validate([
            'ente' => 'required|string',
            'tipo_login' => 'required|string',
        ]);
        $session = $fiscoApi->avviaSessione($request->ente, $request->tipo_login);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Errore avvio sessione'], 500);
        }
        return response()->json(['success' => true, 'session' => $session]);
    }

    /**
     * Webhook: riceve aggiornamenti di stato/qr_code da FiscoApi.
     */
    public function webhook(Request $request, FiscoApiService $fiscoApi)
    {
        $payload = $request->all();
        $session = $fiscoApi->aggiornaSessioneDaWebhook($payload);

        Log::info('Webhook fiscoapi ricevuto', ['payload' => $payload]);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Sessione non trovata'], 404);
        }

        if($session->stato == 'sessione_attiva'){
            Artisan::call('fiscoapi:post-login', ['id_sessione' => $session->id_sessione]);
        }

        return response()->json(['success' => true, 'session' => $session]);
    }
}
