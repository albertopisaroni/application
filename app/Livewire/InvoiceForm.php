<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use App\Models\InvoiceNumbering;
use App\Models\InvoiceTemplate;
use Illuminate\Support\Facades\Storage;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\File;


class InvoiceForm extends Component
{
    public $company;
    public $clients = [];
    public $paymentMethods = [];
    public $numberings = [];
    public $selectedClientId;
    public $selectedPaymentMethodId;
    public $selectedNumberingId;
    public $invoiceDate;
    public $dueDateOption = '60';
    public $withholdingTax = false;
    public $inpsContribution = false;
    public $headerNotes;
    public $footerNotes;
    public $saveNotesForFuture = false;
    public $globalDiscount = 0.00;

    public float $subtotal = 0.00;
    public float $vat = 0.00;
    public float $total = 0.00;
    public float $totalWithholdingTax = 0.00;

    public $items = [];

    public $invoicePrefix = '';
    public $invoiceNumber = '';

    public $client_search = '';
    public $clientSuggestions = [];

    public $templateHtml = '';

    protected $rules = [
        'selectedClientId' => 'required|exists:clients,id',
        'invoiceDate' => 'required|date',
        'selectedNumberingId' => 'required|exists:invoice_numberings,id',
        'items.*.name' => 'required|string',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.vat_rate' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->company = auth()->user()->companies()->find(session('current_company_id'));

        if (!$this->company) {
            abort(403, 'Nessuna azienda selezionata.');
        }

        $this->clients = $this->company->clients()->get();
        $this->paymentMethods = $this->company->paymentMethods()->get();
        $this->numberings = $this->company->invoiceNumberings()->get();
        $this->invoiceDate = today()->toDateString();

        // Recupera ultima numerazione usata, oppure la prima disponibile
        $lastInvoice = $this->company->invoices()->latest('issue_date')->first();
        $defaultNumberingId = $lastInvoice?->numbering_id ?? $this->numberings->first()?->id;

        $this->selectedNumberingId = $defaultNumberingId;

        // Applica numerazione selezionata
        if ($this->selectedNumberingId) {
            $this->updatedSelectedNumberingId($this->selectedNumberingId);
        }

        // Imposta un articolo di default
        $this->addItem();
    }

    public function save()
    {
        $this->validate();

        // Trova i modelli collegati
        $client = Client::findOrFail($this->selectedClientId);
        $numbering = InvoiceNumbering::findOrFail($this->selectedNumberingId);
        $paymentMethod = PaymentMethod::find($this->selectedPaymentMethodId);

        // Calcoli totali aggiornati
        $this->recalculateTotals();

        // Crea la fattura
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $client->id,
            'numbering_id' => $this->selectedNumberingId,
            'invoice_number' => $this->invoicePrefix . $this->invoiceNumber,
            'issue_date' => $this->invoiceDate,
            'fiscal_year' => \Carbon\Carbon::parse($this->invoiceDate)->format('Y'),
            'withholding_tax' => $this->withholdingTax,
            'inps_contribution' => $this->inpsContribution,
            'payment_methods_id' => $paymentMethod?->id,
            'subtotal' => $this->subtotal,
            'vat' => $this->vat,
            'total' => $this->total,
            'global_discount' => $this->globalDiscount,
            'header_notes' => $this->headerNotes,
            'footer_notes' => $this->footerNotes,
            'save_notes_for_future' => $this->saveNotesForFuture,
            'client_name' => $client->name,
            'client_address' => $client->address,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
        ]);

        // Salva le righe
        foreach ($this->items as $item) {
            $invoice->items()->create([
                'name' => $item['name'],
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'],
                'unit_of_measure' => $item['unit_of_measure'] ?? '',
            ]);
        }

        // Aggiorna progressivo
        $numbering->increment('current_number');

        // Salva note se richiesto
        if ($this->saveNotesForFuture) {
            $numbering->default_header_notes = $this->headerNotes;
            $numbering->default_footer_notes = $this->footerNotes;
            $numbering->save();
        }


        // 🔽 GENERA PDF E SALVA SU S3
        $html = $this->renderInvoicePreview();
        $pdf = $this->generatePdf($html);

        $companySlug = $this->company->slug;
        $year = \Carbon\Carbon::parse($this->invoiceDate)->format('Y');
        $invoiceNumber = $this->invoicePrefix . $this->invoiceNumber;
        $path = "$companySlug/fatture/{$this->selectedNumberingId}/$year/Fattura-$invoiceNumber.pdf";

        $finalPath = app()->environment('production') ? $path : "tests/$path";


        // Carica su S3
        Storage::disk('s3')->put($finalPath, $pdf);

        $url = Storage::disk('s3')->temporaryUrl(
            $finalPath,
            now()->addMinutes(30) // oppure addDays(1)
        );


        // Se hai una colonna per salvare il PDF path (es. `pdf_path`)
        $invoice->pdf_path = $finalPath;
        $invoice->save();

        // Recupera tutti i contatti del cliente che vogliono ricevere la fattura
        $recipients = $client->contacts()->where('receives_invoice_copy', 1)->pluck('email')->toArray();

        if (!empty($recipients)) {
            $pdfUrl = Storage::disk('s3')->url($finalPath);
            foreach ($recipients as $email) {
                \Mail::to($email)->send(new \App\Mail\InvoiceMail($invoice, $finalPath));
                \Log::info("📤 Fattura inviata a: $email");
            }
        } else {
            \Log::info("📭 Nessun contatto configurato per ricevere la fattura del cliente: {$client->name}");
        }

        session()->flash('success', 'Fattura salvata con successo.');
        return redirect()->route('fatture.list');
    }

    public function generatePdf($html)
    {
        return Pdf::loadHTML($html)->setPaper('a4')->setWarnings(false)->output();
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function updatedClientSearch($value)
    {
        if (strlen($value) < 2) {
            $this->clientSuggestions = [];
            return;
        }

        $this->clientSuggestions = $this->clients
            ->filter(fn($client) => str($client->name)->lower()->contains(str($value)->lower()))
            ->take(5)
            ->values()
            ->all();
    }

    public function selectClient($id)
    {
        $client = $this->clients->firstWhere('id', $id);

        if ($client) {
            $this->selectedClientId = $client->id;
            $this->client_search = $client->name;
            $this->clientSuggestions = [];
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'name' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'vat_rate' => 0,
            'unit_of_measure' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        $this->recalculateTotals();
    }

    public function updatedSelectedNumberingId($value)
    {
        $numbering = InvoiceNumbering::find($value);

        $this->invoicePrefix = $numbering->prefix ?? '';
        $this->invoiceNumber = $numbering->getNextNumericPart();

        if ($numbering->save_notes_for_future) {
            $this->headerNotes = $numbering->default_header_notes;
            $this->footerNotes = $numbering->default_footer_notes;
        }

        // Carica template HTML associato alla numerazione
        if ($numbering->template_id) {
            $template = InvoiceTemplate::find($numbering->template_id);
            $this->templateHtml = $template?->blade ?? '';
        }
    }

    public function updated($property)
    {
        if (str_starts_with($property, 'items.') || $property === 'globalDiscount') {
            $this->recalculateTotals();
        }
    }

    public function calculateTotals()
    {
        $subtotal = 0;
        $vatTotal = 0;

        foreach ($this->items as $item) {
            $lineTotal = floatval($item['quantity']) * floatval($item['unit_price']);
            $subtotal += $lineTotal;
            $vatTotal += $lineTotal * ($item['vat_rate'] / 100);
        }

        return [
            'subtotal' => $subtotal,
            'vat' => $vatTotal,
            'total' => $subtotal + $vatTotal - floatval($this->globalDiscount),
        ];
    }

    public function recalculateTotals()
    {
        $subtotal = 0;
        $vatTotal = 0;

        foreach ($this->items as $item) {
            $quantity = floatval($item['quantity'] ?? 0);
            $price = floatval($item['unit_price'] ?? 0);
            $discount = floatval($item['discount'] ?? 0);
            $vat = floatval($item['vat_rate'] ?? 0);

            $lineTotal = $quantity * $price;
            $subtotal += $lineTotal;
            $vatTotal += $lineTotal * ($vat / 100);
        }

        $this->subtotal = $subtotal;
        $this->vat = $vatTotal;
        $this->total = $subtotal + $vatTotal - floatval($this->globalDiscount ?? 0);
    }

    public function render()
    {
        $totals = $this->calculateTotals();

        return view('livewire.invoice-form', [
            'totals' => $totals,
        ]);
    }

    public function renderInvoicePreview(): string
    {

        $company = $this->company;
        $numbering = InvoiceNumbering::find($this->selectedNumberingId);
        $client = Client::find($this->selectedClientId);


        $invoiceRows = '';
        foreach ($this->items as $index => $item) {
            $quantity   = $item['quantity'];
            $unitPrice  = number_format(floatval($item['unit_price']), 2, ',', '.');
            $vatRate    = $item['vat_rate'];
            $lineTotal  = number_format(floatval($item['quantity']) * floatval($item['unit_price']), 2, ',', '.');

            $name = e($item['name']);
            $desc = e($item['description'] ?? '');
            $fullDescription = $desc ? "$name - $desc" : $name;

            $invoiceRows .= '<tr>
                <td class="border-b py-3 pl-3">' . ($index + 1) . '</td>
                <td class="border-b py-3 pl-2">' . $fullDescription . '</td>
                <td class="border-b py-3 pl-2 text-right">' . $quantity . '</td>
                <td class="border-b py-3 pl-2 text-right">€' . $unitPrice . '</td>
                <td class="border-b py-3 pl-2 text-right">' . $vatRate . '%</td>
                <td class="border-b py-3 pl-2 pr-4 text-right">€' . $lineTotal . '</td>
            </tr>';
        }


        $variables = [
            '{{ $invoiceDate }}' => $this->invoiceDate,
            '{{ $invoiceNumber }}' => $this->invoicePrefix . $this->invoiceNumber,
            '{{ $paymentIban }}' => optional(PaymentMethod::find($this->selectedPaymentMethodId))->iban ?? '',

            // Cliente
            '{{ $clientName }}'     => $client?->name ?? '',
            '{{ $clientPIVA }}'     => $client?->piva ?? '',
            '{{ $clientAddress }}'  => $client?->address ?? '',
            '{{ $clientCAP }}'      => $client?->cap ?? '',
            '{{ $clientCity }}'     => $client?->city ?? '',
            '{{ $clientProvince }}' => $client?->province ?? '',

            // Azienda
            '{{ $companyName }}' => $company->legal_name ?? $company->name,
            '{{ $companyVat }}' => $company->piva ?? '',

            '{{ $companyReaBlock }}' => $company->tax_code
                ? "<p>R.E.A: {$company->tax_code}</p>"
                : '',

            '{{ $companyEmailBlock }}' => $company->email
                ? "<p>Email: {$company->email}</p>"
                : '',

            '{{ $companyPecBlock }}' => $company->pec_email
                ? "<p>PEC: {$company->pec_email}</p>"
                : '',

            '{{ $globalDiscountBlock }}' => $this->globalDiscount > 0
                ? <<<HTML
                    <tr>
                        <td class="border-b p-3 w-full"></td>
                        <td class="border-b p-3">
                            <div class="whitespace-nowrap text-neutral-700">Sconto:</div>
                        </td>
                        <td class="border-b p-3 text-right">
                            <div class="whitespace-nowrap text-neutral-700">€ $this->globalDiscount</div>
                        </td>
                    </tr>
                HTML
                : '',


            '{{ $headerNotesBlock }}' => $this->headerNotes
                ? <<<HTML
                    <div class="px-14 pt-8 text-sm text-neutral-700">
                        <p style="color: #0d172b" class="font-bold">Intestazione:</p>
                        <p>$this->headerNotes</p>
                    </div>
                HTML
                : '',

            '{{ $footerNotesBlock }}' => $this->footerNotes
                ? <<<HTML
                    <div class="px-14 py-2 text-sm text-neutral-700">
                        <p style="color: #0d172b" class="font-bold">Note aggiuntive:</p>
                        <p>$this->footerNotes</p>
                    </div>
                HTML
                : '',

            '{{ $paymentMethodBlock }}' => $this->selectedPaymentMethodId
                ? "<p><strong>Pagamento:</strong> " . e(optional($this->paymentMethods->find($this->selectedPaymentMethodId))->name) . "</p>"
                : '',

            // Logo
            '{{ $companyLogo }}'    => $numbering?->logo_base64 ?? '',

            // Totali
            '{{ $invoiceRows }}' => $invoiceRows,

            '{{ $subtotal }}' => number_format($this->subtotal, 2, ',', '.'),
            '{{ $vatTotal }}' => number_format($this->vat, 2, ',', '.'),
            '{{ $price }}'    => number_format($this->total, 2, ',', '.'),

        ];

        return str_replace(array_keys($variables), array_values($variables), $this->templateHtml);
    }
}
