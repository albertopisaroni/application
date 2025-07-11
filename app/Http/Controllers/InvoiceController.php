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

    /**
     * Mostra l'elenco delle note di credito.
     */
    public function creditNotesList()
    {
        return view('note-di-credito.lista');
    }

    /**
     * Mostra il form per la creazione di una nuova nota di credito.
     */
    public function createCreditNote()
    {
        return view('note-di-credito.nuova');
    }

    /**
     * Salva la nuova nota di credito nel database.
     * Questo metodo non è più utilizzato poiché la logica è gestita dal componente Livewire CreditNoteForm.
     */
    public function storeCreditNote(Request $request)
    {
        // La logica di salvataggio è ora gestita dal componente Livewire CreditNoteForm
        return redirect()->route('note-di-credito.lista');
    }

    /**
     * Mostra l'elenco delle autofatture.
     */
    public function selfInvoicesList()
    {
        // Verifica che l'azienda non sia in regime forfettario
        $company = auth()->user()->current_company;
        if ($company->forfettario) {
            abort(403, 'Le autofatture non sono disponibili per aziende in regime forfettario.');
        }

        return view('autofatture.lista');
    }

    /**
     * Mostra il form per la creazione di una nuova autofattura.
     */
    public function createSelfInvoice()
    {
        // Verifica che l'azienda non sia in regime forfettario
        $company = auth()->user()->current_company;
        if ($company->forfettario) {
            abort(403, 'Le autofatture non sono disponibili per aziende in regime forfettario.');
        }

        $companyId = auth()->user()->current_company_id;
        $numberings = InvoiceNumbering::where('company_id', $companyId)->get();

        return view('autofatture.nuova', compact('numberings'));
    }

    /**
     * Salva la nuova autofattura nel database.
     */
    public function storeSelfInvoice(Request $request)
    {
        // Verifica che l'azienda non sia in regime forfettario
        $company = auth()->user()->current_company;
        if ($company->forfettario) {
            abort(403, 'Le autofatture non sono disponibili per aziende in regime forfettario.');
        }

        $data = $request->validate([
            'numbering_id'   => 'required|exists:invoice_numberings,id',
            'invoice_date'   => 'required|date',
            'document_type'  => 'required|in:TD16,TD17,TD18,TD19,TD20,TD21,TD27',
            'client_name'    => 'required|string',
            'client_address' => 'nullable|string',
            'client_email'   => 'nullable|email',
            'client_phone'   => 'nullable|string',
            'items'          => 'nullable|array',
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
        $data['invoice_number'] = $generatedNumber;

        // Crea l'autofattura
        $invoice = Invoice::create($data);

        // Crea gli articoli se presenti
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'vat_rate' => $item['vat'],
                ]);
            }
        }

        return redirect()->route('autofatture.lista')->with('success', 'Autofattura creata con successo!');
    }
}
