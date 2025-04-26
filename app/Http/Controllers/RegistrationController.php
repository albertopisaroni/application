<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Registration;

class RegistrationController extends Controller
{
    public function signatureCallback(Request $request)
    {
        // Verifica autenticazione con X-Signature-Key
        $expectedKey = config('services.openapi.signature.key');

        if ($request->header('X-Signature-Key') !== $expectedKey) {
            Log::warning('Tentativo di callback non autorizzato');
            abort(403, 'Accesso non autorizzato');
        }

        // ✅ Leggi i dati ricevuti
        $data = $request->all();

        // Esempio: trova la registrazione da email o UUID
        $email = data_get($data, 'members.0.email');
        $status = data_get($data, 'status');

        Log::info('Firma completata', compact('email', 'status'));

        // Se vuoi, aggiorna la registrazione come "firmata"
        if ($email && $status === 'SIGNED') {
            Registration::where('email', $email)->update([
                'signed_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function signatureRedirect(Request $request, string $type)
    {
        $uuid = session('uuid'); // oppure usa un token firmato se vuoi più sicurezza

        if (!$uuid) {
            return redirect('/')->with('error', 'Sessione scaduta. Riprova.');
        }

        $registration = Registration::where('uuid', $uuid)->first();

        if (!$registration) {
            return redirect('/')->with('error', 'Registrazione non trovata.');
        }

        if ($type === 'success') {
            $registration->update([
                'signed_at' => now(),
                'step' => 10, // o step completato
            ]);

            return redirect()->route('guest.onboarding', ['uuid' => $uuid]);
        }

        if ($type === 'cancel') {
            return redirect()->route('guest.onboarding', ['uuid' => $uuid])->with('error', 'Hai annullato la firma.');
        }

        if ($type === 'error') {
            return redirect()->route('guest.onboarding', ['uuid' => $uuid])->with('error', 'Si è verificato un errore nella firma.');
        }

        return redirect('/');
    }
}
