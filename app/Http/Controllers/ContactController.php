<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Company;
use Illuminate\Support\Facades\Http;

class ContactController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        if (! $companyId) {
            abort(403, 'Nessuna company selezionata');
        }

        $clients = Client::where('company_id', $companyId)
                         ->where('hidden', false)
                         ->get();

        return view('contatti.clienti.lista', compact('clients'));
    }

    

    public function create()
    {
        return view('contatti.clienti.nuovo');
    }

    public function store(Request $request)
    {
        $company = auth()->user()->currentCompany;
    
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'cap' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'piva' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($company) {
                    $exists = $company->clients()
                        ->where('piva', $value)
                        ->where('hidden', false)
                        ->exists();
    
                    if ($exists) {
                        $fail('Un cliente con questa P.IVA esiste già.');
                    }
                },
            ],
            'sdi' => 'nullable|string|max:50',
            'pec' => 'nullable|email|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
        ]);
    
        // Riattiva cliente esistente (hidden = true) oppure crea nuovo
        $existing = $company->clients()
            ->where('piva', $validated['piva'])
            ->where('hidden', true)
            ->first();
    
        if ($existing) {
            $existing->update(array_merge($validated, ['hidden' => false]));
    
            return redirect()->route('contatti.clienti.lista')->with('success', 'Cliente riattivato.');
        }
    
        $company->clients()->create($validated);
    
        return redirect()->route('contatti.clienti.lista')->with('success', 'Cliente creato con successo.');
    }

    public function show(Client $client)
    {
        $client->load('contacts');
        return view('contatti.clienti.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('contatti.clienti.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'cap' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'piva' => 'nullable|string|max:255',
            'sdi' => 'nullable|string|max:255',
            'pec' => 'nullable|email|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $client->update($validated);

        return redirect()->route('conatti.clienti.show', $client)->with('success', 'Cliente aggiornato con successo.');
    }


    public function clientStore(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'receives_invoice_copy' => 'nullable|boolean',
            'receives_notifications' => 'nullable|boolean',
        ]);

        $data['receives_invoice_copy'] = $request->has('receives_invoice_copy');
        $data['receives_notifications'] = $request->has('receives_notifications');

        // Se è il primo contatto per quel client, segna come main
        $data['is_main_contact'] = $client->contacts()->count() === 0;

        $client->contacts()->create($data);

        return back()->with('success', 'Contatto aggiunto con successo.');
    }


    public function clientDestroy(Contact $contact)
    {
        $client = $contact->client;
        $contact->delete();

        return redirect()->route('contatti.clienti.show', $client)->with('success', 'Contatto eliminato con successo.');
    }

    public function clientEdit(Contact $contact)
    {
        return view('app.contatti.clienti.contacts.edit', compact('contact'));
    }

    public function clientUpdate(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'receives_invoice_copy' => 'nullable|boolean',
            'receives_notifications' => 'nullable|boolean',
        ]);

        $validated['receives_invoice_copy'] = $request->has('receives_invoice_copy');
        $validated['receives_notifications'] = $request->has('receives_notifications');

        $contact->update($validated);

        return redirect()->route('contatti.clienti.show', $contact->client_id)
                        ->with('success', 'Contatto aggiornato con successo.');
    }


    public function createLookup()
    {
        return view('app.contatti.clienti.lookup');
    }

    public function hide(Client $client)
    {
        $client->update(['hidden' => true]);

        return back()->with('success', 'Cliente nascosto con successo.');
    }


    public function createLookupPost(Request $request)
    {
        $request->validate([
            'piva' => 'required|string',
        ]);

        $piva = $request->piva;

        if (strpos($piva, 'IT') === 0) {
            $piva = substr($piva, 2);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
        ])->get(env("OPENAPI_COMPANY_URL") . '/IT-start/' . $piva);

        if (! $response->successful()) {
            return back()->withErrors(['piva' => 'Impossibile recuperare i dati aziendali.']);
        }

        $data = $response->json()['data'][0];
        $address = $data['address']['registeredOffice'] ?? [];

        $clientData = [
            'name' => $data['companyName'] ?? '',
            'piva' => $piva,
            'address' => $address['streetName'] ?? '',
            'cap' => $address['zipCode'] ?? '',
            'city' => $address['town'] ?? '',
            'province' => $address['province'] ?? '',
            'country' => 'IT',
            'sdi' => $data['sdiCode'] ?? '',
            'pec' => null,
        ];

        if ($clientData['sdi'] === '0000000') {
            $pecResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openapi.company.token'),
            ])->get(env("OPENAPI_COMPANY_URL") . '/IT-pec/' . $piva);

            if ($pecResponse->successful()) {
                $clientData['pec'] = $pecResponse->json()['data'][0]['pec'] ?? null;
            }
        }

        // Reindirizza all'edit ma passando i dati come old() o session
        return redirect()->route('contatti.clienti.nuovo')
            ->withInput($clientData)
            ->with('autofill', true);
    }

}