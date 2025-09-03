<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use App\Models\InvoiceNumbering;
use App\Models\Client;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurringInvoiceController extends Controller
{
    /**
     * Display a listing of recurring invoices.
     */
    public function index()
    {
        return view('fatture-ricorrenti.lista');
    }

    /**
     * Show the form for creating a new recurring invoice.
     */
    public function create()
    {
        $companyId = Auth::user()->current_company_id;
        
        $numberings = InvoiceNumbering::where('company_id', $companyId)->get();
        $clients = Client::where('company_id', $companyId)->get();
        $paymentMethods = PaymentMethod::where('company_id', $companyId)->get();

        return view('fatture-ricorrenti.nuova', compact('numberings', 'clients', 'paymentMethods'));
    }

    /**
     * Store a newly created recurring invoice in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'numbering_id' => 'required|exists:invoice_numberings,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'template_name' => 'nullable|string|max:255',
            'header_notes' => 'nullable|string',
            'footer_notes' => 'nullable|string',
            'contact_info' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'global_discount' => 'nullable|numeric|min:0',
            'withholding_tax' => 'boolean',
            'inps_contribution' => 'boolean',
            'recurrence_type' => 'required|in:days,weeks,months,years',
            'recurrence_interval' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'max_invoices' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0',
        ]);

        // Calculate totals from items
        $subtotal = 0;
        $totalVat = 0;
        
        foreach ($data['items'] as &$item) {
            $itemSubtotal = $item['quantity'] * $item['unit_price'];
            $itemVat = $itemSubtotal * ($item['vat_rate'] / 100);
            $item['total'] = $itemSubtotal + $itemVat;
            
            $subtotal += $itemSubtotal;
            $totalVat += $itemVat;
        }
        
        $data['subtotal'] = $subtotal;
        $data['vat'] = $totalVat;
        $data['total'] = $subtotal + $totalVat;
        $data['company_id'] = Auth::user()->current_company_id;
        $data['next_invoice_date'] = $data['start_date'];

        $recurringInvoice = RecurringInvoice::create($data);

        // Create items
        foreach ($data['items'] as $item) {
            $recurringInvoice->items()->create($item);
        }

        return redirect()->route('fatture-ricorrenti.lista')
            ->with('success', 'Fattura ricorrente creata con successo!');
    }

    /**
     * Display the specified recurring invoice.
     */
    public function show(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('view', $recurringInvoice);
        
        return view('fatture-ricorrenti.show', compact('recurringInvoice'));
    }

    /**
     * Show the form for editing the specified recurring invoice.
     */
    public function edit(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);
        
        $companyId = Auth::user()->current_company_id;
        
        $numberings = InvoiceNumbering::where('company_id', $companyId)->get();
        $clients = Client::where('company_id', $companyId)->get();
        $paymentMethods = PaymentMethod::where('company_id', $companyId)->get();

        return view('fatture-ricorrenti.edit', compact('recurringInvoice', 'numberings', 'clients', 'paymentMethods'));
    }

    /**
     * Update the specified recurring invoice in storage.
     */
    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'numbering_id' => 'required|exists:invoice_numberings,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'template_name' => 'nullable|string|max:255',
            'header_notes' => 'nullable|string',
            'footer_notes' => 'nullable|string',
            'contact_info' => 'nullable|string',
            'subtotal' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'global_discount' => 'nullable|numeric|min:0',
            'withholding_tax' => 'boolean',
            'inps_contribution' => 'boolean',
            'recurrence_type' => 'required|in:days,weeks,months,years',
            'recurrence_interval' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_invoices' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
        ]);

        $recurringInvoice->update($data);

        // Update items
        $recurringInvoice->items()->delete();
        foreach ($data['items'] as $item) {
            $recurringInvoice->items()->create($item);
        }

        // Recalculate next invoice date if necessary
        if ($recurringInvoice->wasChanged(['recurrence_type', 'recurrence_interval'])) {
            $recurringInvoice->updateNextInvoiceDate();
        }

        return redirect()->route('fatture-ricorrenti.lista')
            ->with('success', 'Fattura ricorrente aggiornata con successo!');
    }

    /**
     * Remove the specified recurring invoice from storage.
     */
    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('delete', $recurringInvoice);
        
        $recurringInvoice->delete();

        return redirect()->route('fatture-ricorrenti.lista')
            ->with('success', 'Fattura ricorrente eliminata con successo!');
    }

    /**
     * Toggle the active status of a recurring invoice.
     */
    public function toggleActive(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);
        
        $recurringInvoice->update([
            'is_active' => !$recurringInvoice->is_active
        ]);

        $status = $recurringInvoice->is_active ? 'attivata' : 'disattivata';
        
        return redirect()->back()
            ->with('success', "Fattura ricorrente {$status} con successo!");
    }
}
