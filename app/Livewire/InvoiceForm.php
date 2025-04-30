<?php

namespace App\Livewire;

use Livewire\Component;

use App\Jobs\SendInvoiceToSdiJob;
use App\Services\InvoiceRenderer;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use App\Models\InvoiceNumbering;
use App\Models\InvoiceTemplate;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

use Illuminate\Http\File;
use Barryvdh\DomPDF\Facade\Pdf;


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

    public bool $splitPayments = false;
    public string $dueOption = 'on_receipt'; // 'on_receipt'|'15'|'30'|'custom'
    public bool   $customDue = false;
    public string $dueDate; // verrà popolata da setDue()

    public $ddt_number;
    public $ddt_date;

    public string $documentType = 'TD01';   // TD01, TD24, TD25 …
    public array  $documentTypes = [
        'TD01' => 'Fattura immediata',
        'TD01_ACC' => 'Fattura accompagnatoria', // user-friendly, poi mappi sotto
        'TD24' => 'Fattura differita (beni)',
        'TD25' => 'Fattura differita (servizi)',
    ];
    

    public $payments = [
        [
          'date'       => null,
          'value'      => 0.00,
          'type'       => 'percent',  // 'amount' oppure 'percent'
          'term'       => '15',
        ],
    ];

    public array $termsOptions = [
        '15'      => '15 gg',
        '30'      => '30 gg',
        '60'      => '60 gg',
        '90'      => '90 gg',
        '150'     => '150 gg',
        '30_fm'   => '30 gg f.m.',
        '60_fm'   => '60 gg f.m.',
        'custom'  => 'Personalizzato',
    ];

    public array $modalitaSdi = [
        'MP01' => 'Contanti',
        'MP02' => 'Assegno bancario',
        'MP03' => 'Assegno circolare',
        'MP04' => 'Vaglia cambiario',
        'MP05' => 'Bonifico',
        'MP06' => 'Rimessa diretta',
        'MP07' => 'Bollettino bancario',
        'MP08' => 'Carta di credito',
        'MP09' => 'RID – addebito diretto su conto corrente',
        'MP10' => 'RID – pagamento mediante SDD',
        'MP11' => 'RID – altri tipi',
        'MP12' => 'RIBA',
        'MP13' => 'MAV',
        'MP14' => 'Quietanza erario',
        'MP15' => 'Giroconto su conti di contabilità speciale',
        'MP16' => 'PagoPA',
        'MP17' => 'Bollo virtuale',
        'MP18' => 'Trattenute su somme già riscosse',
        'MP19' => 'Anticipo',
        'MP20' => 'Credito cartolare',
        'MP21' => 'Titoli di credito',
        'MP22' => 'Credito su vendite',
        'MP23' => 'Altro metodo di pagamento',
    ];

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
        'documentType' => 'required|in:TD01,TD01_ACC,TD24,TD25',
        'ddt_number' => 'exclude_unless:documentType,TD24,TD25|required|string',
        'ddt_date'   => 'exclude_unless:documentType,TD24,TD25|required|date',    
    ];

    public function mount()
    {
        $this->company = auth()->user()->companies()->find(session('current_company_id'));

        if (!$this->company) {
            abort(403, 'Nessuna azienda selezionata.');
        }

        $this->dueDate = today()->toDateString();

        $this->payments = [
            [
                'type'  => 'percent',  // 'percent' o 'amount'
                'value' => 50,
                'term'  => '30',       // codice fra quelli di $termsOptions
                'date'  => now()->addDays(30)->toDateString(),
            ],
            [
                'type'  => 'percent',
                'value' => 50,
                'term'  => '30',
                'date'  => now()->addDays(30)->toDateString(),
            ],
        ];

        $this->clients = $this->company->clients()->get();
        $this->paymentMethods = $this->company->paymentMethods()->get();
        $this->numberings     = $this->company->invoiceNumberings()->get();
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

    public function addPayment()
    {
        // prendo il tipo correntemente selezionato (se non c'è nulla, di default 'amount')
        $defaultType = $this->payments[0]['type'] ?? 'amount';

        // aggiungo la nuova rata con lo stesso type
        $this->payments[] = [
            'value' => 0.00,
            'type'  => $defaultType,
            'term'  => 'custom',   // o un termine di default che preferisci
            'date'  => null,
        ];

        // ricalcolo sempre l'ultima rata perché mantenga 100% o totale
        $this->recalcLast();
    }

    public function updatedSplitPayments($val)
    {
        if ($val && count($this->payments) < 2) {
            // inizializza due rate 50/50
            $this->mount();
        }
    }

    public function updatedPayments($val, $key)
    {
        // quando cambia qualsiasi payments.*.value o payments.*.type
        // ricalcola l’ultima rata
        $this->recalcLast();
    }

    public function updatedPaymentsTerm($val, $key)
    {
        // payments.{i}.term cambiato
        // ricalcola la data
        if (str($key)->endsWith('.term')) {
            [$_, $i, $_] = explode('.', $key);
            $term = $this->payments[$i]['term'];
            if ($term !== 'custom') {
                $days = match($term) {
                  '15'    => 15,
                  '30'    => 30,
                  '60'    => 60,
                  '90'    => 90,
                  '150'   => 150,
                  '30_fm' => now()->endOfMonth()->diffInDays(now()),
                  '60_fm' => now()->addMonth()->endOfMonth()->diffInDays(now()),
                };
                $this->payments[$i]['date'] = now()->addDays($days)->toDateString();
            }
        }
    }

    public function updatedPaymentsDate($val, $key)
    {
        // se l’utente modifica a mano la data, imposta term = 'custom'
        if (str($key)->endsWith('.date')) {
            [$_, $i, $_] = explode('.', $key);
            $this->payments[$i]['term'] = 'custom';
        }
    }

    protected function recalcLast()
    {
        $n = count($this->payments);
        if ($n < 2) return;
        // se tipo percentuale
        if ($this->payments[0]['type'] === 'percent') {
            $sum = 0;
            for ($i = 0; $i < $n - 1; $i++) {
                $sum += floatval($this->payments[$i]['value']);
            }
            $this->payments[$n - 1]['value'] = max(0, 100 - $sum);
        } else {
            // tipo importo: usa $this->total
            $sum = 0;
            for ($i = 0; $i < $n - 1; $i++) {
                $sum += floatval($this->payments[$i]['value']);
            }
            $this->payments[$n - 1]['value'] = max(0, $this->total - $sum);
        }
    }

    public function setDue(string $opt)
    {
        $this->dueOption = $opt;
        $this->customDue = $opt === 'custom';

        if (! $this->customDue) {
            $days = match($opt) {
                'on_receipt' => 0,
                '15'         => 15,
                '30'         => 30,
                default      => 0,
            };
            $this->dueDate = now()->addDays($days)->toDateString();
        }
    }

    public function save()
    {
        // --- controllo somme rate --------------------------------------------
        if ($this->splitPayments) {
            $type = $this->payments[0]['type'] ?? 'amount';
            $sum  = collect($this->payments)->sum(fn($p) => floatval($p['value']));
            if ($type === 'percent' && round($sum, 2) !== 100.00) {
                $this->addError('payments', "La somma delle percentuali deve essere 100% (hai $sum%).");
                return;
            }
            if ($type === 'amount' && round($sum, 2) !== round($this->total, 2)) {
                $this->addError('payments', "La somma degli importi (€$sum) deve corrispondere al totale (€{$this->total}).");
                return;
            }
        }

        $this->validate();

        if (! $this->splitPayments) {
            $this->payments = [[
                'date'  => $this->dueDate,
                'value' => $this->total,
                'type'  => 'amount',
            ]];
        }

        // Trova i modelli collegati
        $client        = Client::findOrFail($this->selectedClientId);
        $numbering     = InvoiceNumbering::findOrFail($this->selectedNumberingId);
        $paymentMethod = PaymentMethod::find($this->selectedPaymentMethodId);
        $sdiMode       = $paymentMethod?->sdi_code ?? 'MP05';
        $iban          = $paymentMethod?->iban;

        // Calcoli totali aggiornati
        $this->recalculateTotals();

        DB::beginTransaction();

        try {
            // 1) Creo la fattura
            $invoice = Invoice::create([
                'company_id'           => $this->company->id,
                'client_id'            => $client->id,
                'numbering_id'         => $this->selectedNumberingId,
                'invoice_number'       => $this->invoicePrefix . $this->invoiceNumber,
                'issue_date'           => $this->invoiceDate,
                'fiscal_year'          => Carbon::parse($this->invoiceDate)->format('Y'),
                'withholding_tax'      => $this->withholdingTax,
                'inps_contribution'    => $this->inpsContribution,
                'payment_method_id'    => $paymentMethod?->id,
                'subtotal'             => $this->subtotal,
                'vat'                  => $this->vat,
                'total'                => $this->total,
                'global_discount'      => $this->globalDiscount,
                'header_notes'         => $this->headerNotes,
                'document_type'        => $this->documentType,
                'footer_notes'         => $this->footerNotes,
                'save_notes_for_future'=> $this->saveNotesForFuture,
                'sdi_sent_at'   => null,
                'sdi_received_at' => null,
                'sdi_attempt'   => 1,
            ]);

            // 2) Salvo le righe
            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'name'            => $item['name'],
                    'description'     => $item['description'] ?? '',
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'vat_rate'        => $item['vat_rate'],
                    'unit_of_measure' => $item['unit_of_measure'] ?? '',
                ]);
            }

            // 3) Aggiorno il progressivo
            $numbering->increment('current_number');

            // 4) Salvo le note se richiesto
            if ($this->saveNotesForFuture) {
                $numbering->default_header_notes = $this->headerNotes;
                $numbering->default_footer_notes = $this->footerNotes;
                $numbering->save();
            }

            foreach ($this->payments as $p) {
                $raw    = floatval($p['value']);
                $amount = ($p['type'] ?? 'amount') === 'percent'
                    ? round($invoice->total * $raw / 100, 2)
                    : $raw;
            
                $invoice->paymentSchedules()->create([
                    'due_date' => $p['date'],
                    'amount'   => $amount,
                    'type'     => $p['type'],
                    'percent'  => $p['type'] === 'percent' ? $raw : null,
                ]);
            }

            // 5) Genero XML, invio a SDI, PDF, S3, email…
            SendInvoiceToSdiJob::dispatch($invoice->id);

            // Generazione PDF e caricamento S3 (resta invariato)
            $renderer = new InvoiceRenderer($invoice, $this->items, $this->payments, $this->splitPayments, $this->dueDate);
            $pdf = $renderer->renderPdf();

            $companySlug   = $this->company->slug;
            $year          = Carbon::parse($this->invoiceDate)->format('Y');
            $invoiceNumber = $this->invoicePrefix . $this->invoiceNumber;
            $path          = "{$companySlug}/fatture/{$this->selectedNumberingId}/{$year}/{$invoice->invoice_number}.pdf";

            $encrypted = encrypt($pdf);
            Storage::disk('s3')->put($path, $encrypted);

            $invoice->pdf_path = $path;
            $invoice->pdf_url  = config('app.fatture_url')."/{$invoice->uuid}/pdf";
            $invoice->save();

            $recipients = $client->contacts()
                                ->where('receives_invoice_copy', 1)
                                ->pluck('email')
                                ->toArray();

            if (! empty($recipients)) {
                foreach ($recipients as $email) {
                    \Mail::to($email)
                        ->send(new \App\Mail\InvoiceMail($invoice));
                    Log::info("📤 Fattura inviata a: $email");
                }
            } else {
                Log::info("📭 Nessun contatto configurato per il cliente {$client->name}");
            }

            DB::commit();

            session()->flash('success', 'Fattura salvata con successo.');
            return redirect()->route('fatture.list');
        }
        catch (\Throwable $e) {
            DB::rollBack();

            // Ripristino contatore se già incrementato
            if (isset($numbering) && $numbering->wasChanged('current_number')) {
                $numbering->decrement('current_number');
            }
            // Elimino la fattura “orfana”
            if (isset($invoice) && $invoice->exists) {
                $invoice->delete();
            }

            Log::error('Errore salvataggio fattura', ['exception' => $e]);
            $this->addError('save', 'Errore durante il salvataggio: ' . $e->getMessage());
        }
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
    
        $this->headerNotes = $numbering->default_header_notes ?? $this->headerNotes;
        $this->footerNotes = $numbering->default_footer_notes ?? $this->footerNotes;
    
        // Carica template HTML associato alla numerazione
        if ($numbering->template_id) {
            $template = InvoiceTemplate::find($numbering->template_id);
            $this->templateHtml = $template?->blade ?? '';
        }
    
        // → Imposta qui il metodo di pagamento di default
        //   se la numerazione ne ha uno, altrimenti scegli il primo disponibile
        $this->selectedPaymentMethodId = $numbering->default_payment_method_id
            ?? ($this->paymentMethods->first()?->id ?? null);
    }

    public function updated($propertyName, $value)
    {
        // A) Cambio € / % in una rata
        if (Str::startsWith($propertyName, 'payments.') && Str::endsWith($propertyName, '.type')) {
            // propaga il nuovo tipo a tutte le rate
            foreach ($this->payments as &$p) {
                $p['type'] = $value;
            }
            unset($p);
    
            // riconverti tutti i valori in base al tipo
            if ($value === 'percent') {
                $this->convertAmountsToPercent();
            } else {
                $this->convertAmountsToEuros();
            }
        }
    
        // B) Ricalcola subtotali articoli & sconto
        if (Str::startsWith($propertyName, 'items.') || $propertyName === 'globalDiscount') {
            $this->recalculateTotals();
        }
    
        // C) Se cambia il termine, aggiorna la data con la tua logica
        if (Str::startsWith($propertyName, 'payments.') && Str::endsWith($propertyName, '.term')) {
            $this->updatedPaymentsTerm($value, $propertyName);
        }
    
        // D) Se cambia la data manualmente, imposta il termine a “custom”
        if (Str::startsWith($propertyName, 'payments.') && Str::endsWith($propertyName, '.date')) {
            $this->updatedPaymentsDate($value, $propertyName);
        }
    }

    protected function convertAmountsToPercent()
    {
        $total = max($this->total, 1);
        $sum   = 0;
        $n     = count($this->payments);

        foreach ($this->payments as $i => &$p) {
            // dal valore in euro → %
            $perc      = round($p['value'] / $total * 100, 2);
            $p['value'] = $perc;
            if ($i < $n - 1) {
                $sum += $perc;
            }
        }
        unset($p);

        // forzo l’ultima rata a chiudere al 100%
        $this->payments[$n - 1]['value'] = round(100 - $sum, 2);
    }

    protected function convertAmountsToEuros()
    {
        $total = $this->total;
        $sum   = 0;
        $n     = count($this->payments);

        foreach ($this->payments as $i => &$p) {
            // da % → valore in euro
            $amt        = round($total * ($p['value'] / 100), 2);
            $p['value'] = $amt;
            if ($i < $n - 1) {
                $sum += $amt;
            }
        }
        unset($p);

        // forzo l’ultima rata a chiudere al totale
        $this->payments[$n - 1]['value'] = round($total - $sum, 2);
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

    public function getPreviewHtmlProperty(): string
    {
        // 1) crea l’istanza in memoria
        $invoice = Invoice::make([
            'company_id'           => $this->company->id,
            'client_id'            => $this->selectedClientId,
            'numbering_id'         => $this->selectedNumberingId,
            'invoice_number'       => $this->invoicePrefix . $this->invoiceNumber,
            'issue_date'           => $this->invoiceDate,
            'fiscal_year'          => Carbon::parse($this->invoiceDate)->format('Y'),
            'withholding_tax'      => $this->withholdingTax,
            'inps_contribution'    => $this->inpsContribution,
            'payment_method_id'    => $this->selectedPaymentMethodId,
            'subtotal'             => $this->subtotal,
            'vat'                  => $this->vat,
            'total'                => $this->total,
            'global_discount'      => $this->globalDiscount,
            'header_notes'         => $this->headerNotes,
            'document_type'        => $this->documentType,
            'footer_notes'         => $this->footerNotes,
            'save_notes_for_future'=> $this->saveNotesForFuture,
            'sdi_sent_at'          => null,
            'sdi_received_at'      => null,
            'sdi_attempt'          => 1,
        ]);
    
        // 2) carica numbering + template dal DB
        $numbering = InvoiceNumbering::with('template')
                      ->findOrFail($this->selectedNumberingId);
    
        // 3) “attacca” la relazione al model non-persisted
        $invoice->setRelation('numbering', $numbering);
    
       
        $renderer = new InvoiceRenderer($invoice, $this->items, $this->payments, $this->splitPayments, $this->dueDate);
    
        return $renderer->renderHtml();
    }

    public function removePayment($index)
    {
        // 1) Rimuovo la rata
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    
        // 2) Se ora ho meno di 2 rate, torno alla scadenza singola
        if (count($this->payments) < 2) {
            $this->splitPayments = false;
        } else {
            // altrimenti ricalcolo l’ultima rata
            $this->recalcLast();
        }
    }

}
