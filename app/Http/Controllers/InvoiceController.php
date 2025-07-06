<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

use App\Models\InvoiceNumbering;

class InvoiceController extends Controller
{
    /**
     * Mostra l'elenco di tutte le fatture.
     */
    public function list()
    {
        
    
        return view('fatture.lista');
    }

    /**
     * Mostra il form per la creazione di una nuova fattura.
     */
    public function create()
    {
        $companyId = auth()->user()->current_company_id;
        $numberings = InvoiceNumbering::where('company_id', $companyId)->get();

        return view('fatture.nuova', compact('numberings'));


        return view('app.fatture.nuova');
    }

    /**
     * Salva la nuova fattura nel database.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'numbering_id'   => 'required|exists:invoice_numberings,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'invoice_date'   => 'required|date',
            // Campi per il cliente e per la fattura...
            'client_name'    => 'required|string',
            'client_address' => 'nullable|string',
            'client_email'   => 'nullable|email',
            'client_phone'   => 'nullable|string',
            'items'          => 'nullable|json',
            'subtotal'       => 'required|numeric',
            'vat'            => 'required|numeric',
            'total'          => 'required|numeric',
        ]);

        $data['company_id'] = auth()->user()->current_company_id;

        // Crea il cliente associato
        $client = \App\Models\Client::create([
            'company_id' => $data['company_id'],
            'name' => $data['client_name'],
            'address' => $data['client_address'] ?? null,
            'email' => $data['client_email'] ?? null,
            'phone' => $data['client_phone'] ?? null,
        ]);
        $data['client_id'] = $client->id;

        // Recupera la numerazione e incrementa il progressivo
        $numbering = \App\Models\InvoiceNumbering::find($data['numbering_id']);
        $generatedNumber = $numbering->nextNumber(date('Y', strtotime($data['invoice_date'])));

        // Se il numero di fattura inserito manualmente è diverso e l'utente l'ha modificato, puoi decidere
        // di accettarlo oppure usare il generato. Ad esempio:
        // $data['invoice_number'] = $request->input('invoice_number');
        // O, per forza, usa quello generato:
        $data['invoice_number'] = $generatedNumber;

        Invoice::create($data);

        return redirect()->route('fatture.index')->with('success', 'Fattura creata con successo');
    }
}
