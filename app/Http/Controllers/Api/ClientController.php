<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    /**
     * @group Clienti
     * Crea un nuovo cliente manualmente.
     *
     * @authenticated
     * @bodyParam name string required Nome del cliente.
     * @bodyParam email string Email del cliente.
     * @bodyParam phone string Telefono.
     * @bodyParam address string Indirizzo.
     * @bodyParam cap string CAP.
     * @bodyParam city string Città.
     * @bodyParam province string Provincia.
     * @bodyParam country string default: IT.
     * @bodyParam piva string Partita IVA.
     * @bodyParam sdi string Codice SDI.
     * @bodyParam pec string PEC.
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Mario Rossi",
     *     "email": "mario@example.com"
     *   }
     * }
     */
    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'domain'   => 'nullable|string|max:255',
            'email'    => 'nullable|email',
            'phone'    => 'nullable|string',
            'address'  => 'nullable|string',
            'cap'      => 'nullable|string',
            'city'     => 'nullable|string',
            'province' => 'nullable|string',
            'country'  => 'nullable|string',
            'piva'     => 'nullable|string|regex:/^\d{11}$/',
            'sdi'      => 'nullable|string',
            'pec'      => 'nullable|email',
        ]);

        $data['company_id'] = $request->company->id;

        $exists = Client::where('company_id', $request->company->id)
            ->where('piva', $data['piva'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'Un cliente con questa Partita IVA esiste già per questa azienda.'
            ], 409);
        }

        $client = Client::create($data);

        return response()->json(['success' => true, 'data' => $client], 201);
    }


    /**
     * @group Clienti
     * Crea un nuovo cliente partendo dalla Partita IVA (lookup automatico).
     *
     * @authenticated
     * @bodyParam piva string required Partita IVA valida italiana.
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "name": "Azienda SRL",
     *     "piva": "01234567890",
     *     "city": "Roma"
     *   }
     * }
     */
    public function storeAutomatic(Request $request)
    {
        $data = $request->validate([
            'piva'     => 'nullable|string|regex:/^\d{11}$/',

        ]);

        // 🔎 Esempio lookup da OpenAPI o altro provider esterno
        $response = \Http::get('https://api.openapi.it/v1/piva/' . $data['piva']);

        if (!$response->ok()) {
            return response()->json(['success' => false, 'error' => 'Partita IVA non trovata'], 404);
        }

        $info = $response->json();

        $exists = Client::where('company_id', $request->company->id)
            ->where('piva', $data['piva'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'Un cliente con questa Partita IVA esiste già per questa azienda.'
            ], 409);
        }

        $client = Client::create([
            'company_id' => $request->company->id,
            'name'       => $info['nome'] ?? 'Sconosciuto',
            'domain'     => null, // Il dominio verrà estratto successivamente se disponibile
            'address'    => $info['indirizzo'] ?? null,
            'cap'        => $info['cap'] ?? null,
            'city'       => $info['comune'] ?? null,
            'province'   => $info['provincia'] ?? null,
            'country'    => 'IT',
            'piva'       => $data['piva'],
        ]);

        return response()->json(['success' => true, 'data' => $client], 201);
    }

}