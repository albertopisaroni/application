<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\InvoiceNumbering;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class RecurringInvoiceForm extends Component
{
    public $company;
    public $clients = [];
    public $paymentMethods = [];
    public $numberings = [];
    public $subscriptions = [];
    
    // Basic fields
    public $selectedClientId;
    public $selectedPaymentMethodId;
    public $selectedNumberingId;
    public $templateName;
    public $headerNotes;
    public $footerNotes;
    public $contactInfo;
    public $globalDiscount = 0.00;
    public $withholdingTax = false;
    public $inpsContribution = false;
    public $saveNotesForFuture = false;
    
    // Invoice form compatibility fields
    public $invoicePrefix;
    public $client_search = '';
    public $clientSuggestions = [];
    public $documentType = 'TD01';
    public $ddt_number;
    public $ddt_date;
    
    // Payment terms (from InvoiceForm)
    public bool $splitPayments = false;
    public string $dueOption = 'on_receipt'; // 'on_receipt'|'15'|'30'|'custom'
    public bool $customDue = false;
    public string $dueDate; // verrà popolata da setDue()
    
    public $payments = [
        [
          'date'       => null,
          'value'      => 0.00,
          'type'       => 'percent',  // 'amount' oppure 'percent'
          'term'       => '15',
        ],
    ];
    
    // Recurrence mode flag
    public $recurrenceMode = 'manual'; // 'manual' or 'stripe'
    
    public array $documentTypes = [
        'TD01' => 'Fattura immediata',
        'TD01_ACC' => 'Fattura accompagnatoria',
        'TD24' => 'Fattura differita (beni)',
        'TD25' => 'Fattura differita (servizi)',
    ];
    
    // Stripe integration
    public $stripeSubscriptionId;
    public $triggerOnPayment = false;
    
    // Recurrence settings
    public $recurrenceType = 'months';
    public $recurrenceInterval = 1;
    public $startDate;
    public $endDate;
    public $maxInvoices;
    
    // Items
    public $items = [];
    public $subtotal = 0;
    public $vat = 0;
    public $total = 0;
    
    // Prefill data from subscription
    public $fromSubscription = null;

    public array $recurrenceTypes = [
        'days' => 'Giorni',
        'weeks' => 'Settimane', 
        'months' => 'Mesi',
        'years' => 'Anni'
    ];

    public array $termsOptions = [
        '15'      => '15 gg',
        '30'      => '30 gg',
        '60'      => '60 gg',
        '90'      => '90 gg',
        '120'     => '120 gg',
        'custom'  => 'Data personalizzata',
    ];

    protected $rules = [
        'selectedClientId' => 'required|exists:clients,id',
        'selectedNumberingId' => 'required|exists:invoice_numberings,id',
        'templateName' => 'nullable|string|max:255',
        'recurrenceType' => 'required|in:days,weeks,months,years',
        'recurrenceInterval' => 'required|integer|min:1',
        'startDate' => 'required|date|after_or_equal:today',
        'endDate' => 'nullable|date|after:startDate',
        'maxInvoices' => 'nullable|integer|min:1',
        'items' => 'required|array|min:1',
        'items.*.name' => 'required|string',
        'items.*.description' => 'nullable|string',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.vat_rate' => 'required|numeric|min:0',
        'stripeSubscriptionId' => 'nullable|string|max:255',
        'triggerOnPayment' => 'nullable|boolean',
        'documentType' => 'required|in:TD01,TD01_ACC,TD24,TD25',
        'ddt_number' => 'exclude_unless:documentType,TD24,TD25|required|string',
        'ddt_date' => 'exclude_unless:documentType,TD24,TD25|required|date',
        'contactInfo' => 'nullable|string',
    ];

    public function mount()
    {
        $this->company = Auth::user()->currentCompany ?? (object)['name' => 'Test Company'];
        $this->startDate = now()->toDateString();
        $this->dueDate = now()->toDateString();

        // Initialize payments
        $this->payments = [
            [
                'type'  => 'percent',
                'value' => 50,
                'term'  => '30',
                'date'  => now()->addDays(30)->toDateString(),
            ],
            [
                'type'  => 'percent',
                'value' => 50,
                'term'  => '30',
                'date'  => now()->addDays(30)->toDateString(),
            ],
        ];
        
        // Load data
        $companyId = Auth::user()?->current_company_id ?? session('current_company_id');
        if (!$companyId) {
            return; // Exit if no company context
        }
        
        $this->clients = Client::where('company_id', $companyId)->get();
        $this->paymentMethods = PaymentMethod::where('company_id', $companyId)->get();
        $this->numberings = InvoiceNumbering::where('company_id', $companyId)->get();
        
        // Load Stripe subscriptions
        $this->subscriptions = Subscription::whereHas('client', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('status', 'active')
            ->with(['client', 'price.product'])
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->stripe_subscription_id,
                    'client_id' => $subscription->client_id,
                    'client_name' => $subscription->client->name ?? 'Cliente sconosciuto',
                    'name' => $subscription->price->product->name ?? 'Abbonamento',
                    'amount' => number_format($subscription->total_with_vat / 100, 2, ',', '.'),
                    'status' => $subscription->status,
                    'period_end' => $subscription->current_period_end->format('d/m/Y')
                ];
            });

        // Handle prefill from subscription
        if (request()->has('from_subscription')) {
            $this->fromSubscription = request()->get('from_subscription');
            $this->prefillFromSubscription();
        }

        // Set default values for immediate preview
        $this->contactInfo = $this->numberings->first()?->contact_info ?? '';
        $this->templateName = 'Template Fattura Ricorrente';
        
        // Set default numbering if available
        if ($this->numberings->isNotEmpty() && !$this->selectedNumberingId) {
            $this->selectedNumberingId = $this->numberings->first()->id;
            $this->invoicePrefix = $this->numberings->first()->prefix ?? '';
        }
        
        // Add default item if none exist
        if (empty($this->items)) {
            $this->addItem();
        }
        
        // Calculate initial totals
        $this->calculateTotals();
    }

    public function prefillFromSubscription()
    {
        $subscription = Subscription::with(['client', 'price.product'])
            ->find($this->fromSubscription);
            
        if ($subscription && $subscription->client->company_id == (Auth::user()?->current_company_id ?? session('current_company_id'))) {
            $this->selectedClientId = $subscription->client_id;
            $this->stripeSubscriptionId = $subscription->stripe_subscription_id;
            $this->triggerOnPayment = true;
            $this->templateName = "Abbonamento {$subscription->price->product->name}";
            
            // Set default numbering
            $this->selectedNumberingId = $this->numberings->first()?->id;
            
            // Prefill item
            $this->items = [
                [
                    'name' => $subscription->price->product->name ?? 'Abbonamento',
                    'description' => "Abbonamento {$subscription->price->product->name}",
                    'quantity' => $subscription->quantity ?? 1,
                    'unit_price' => $subscription->unit_amount / 100,
                    'vat_rate' => $subscription->vat_rate ?? 22,
                ]
            ];
            
            $this->calculateTotals();
        }
    }

    public function addItem()
    {
        // Add default values for better preview
        $itemCount = count($this->items) + 1;
        $this->items[] = [
            'name' => count($this->items) === 0 ? 'Servizio di esempio' : "Articolo {$itemCount}",
            'description' => count($this->items) === 0 ? 'Descrizione del servizio ricorrente' : "Descrizione articolo {$itemCount}",
            'quantity' => 1,
            'unit_price' => count($this->items) === 0 ? 100 : 0,
            'vat_rate' => 22,
        ];
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = 0;
        $this->vat = 0;
        
        foreach ($this->items as $item) {
            $itemSubtotal = $item['quantity'] * $item['unit_price'];
            $itemVat = $itemSubtotal * ($item['vat_rate'] / 100);
            
            $this->subtotal += $itemSubtotal;
            $this->vat += $itemVat;
        }
        
        $this->total = $this->subtotal + $this->vat;
    }

    public function updatedItems()
    {
        $this->calculateTotals();
    }

    public function updatedSelectedNumberingId()
    {
        if ($this->selectedNumberingId) {
            $numbering = InvoiceNumbering::find($this->selectedNumberingId);
            if ($numbering) {
                $this->invoicePrefix = $numbering->prefix ?? '';
            }
        }
    }

    public function updatedClientSearch()
    {
        if (strlen($this->client_search) >= 2) {
            $companyId = Auth::user()?->current_company_id ?? session('current_company_id');
            $this->clientSuggestions = Client::where('company_id', $companyId)
                ->where('name', 'LIKE', '%' . $this->client_search . '%')
                ->limit(5)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name
                    ];
                })
                ->toArray();
        } else {
            $this->clientSuggestions = [];
        }
    }

    public function selectClient($clientId)
    {
        $client = Client::find($clientId);
        if ($client) {
            $this->selectedClientId = $clientId;
            $this->client_search = $client->name;
            $this->clientSuggestions = [];
        }
    }

    public function setDue($option)
    {
        $this->dueOption = $option;
        $this->customDue = ($option === 'custom');
        
        if (!$this->customDue) {
            $baseDate = $this->startDate ? Carbon::parse($this->startDate) : now();
            
            switch ($option) {
                case 'on_receipt':
                    $this->dueDate = $baseDate->toDateString();
                    break;
                case '15':
                    $this->dueDate = $baseDate->addDays(15)->toDateString();
                    break;
                case '30':
                    $this->dueDate = $baseDate->addDays(30)->toDateString();
                    break;
            }
        }
    }

    public function addPayment()
    {
        $this->payments[] = [
            'type'  => 'percent',
            'value' => 0,
            'term'  => '30',
            'date'  => now()->addDays(30)->toDateString(),
        ];
    }

    public function removePayment($index)
    {
        if (count($this->payments) > 1) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
        }
    }

    public function updatedRecurrenceMode()
    {
        if ($this->recurrenceMode === 'stripe') {
            // Reset Stripe fields when switching to Stripe mode
            $this->stripeSubscriptionId = '';
            $this->triggerOnPayment = false;
        } else {
            // Reset manual recurrence fields when switching to manual mode
            $this->recurrenceType = 'months';
            $this->recurrenceInterval = 1;
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $data = [
                'company_id' => Auth::user()?->current_company_id ?? session('current_company_id'),
                'client_id' => $this->selectedClientId,
                'numbering_id' => $this->selectedNumberingId,
                'payment_method_id' => $this->selectedPaymentMethodId,
                'stripe_subscription_id' => $this->recurrenceMode === 'stripe' ? $this->stripeSubscriptionId : null,
                'trigger_on_payment' => $this->recurrenceMode === 'stripe' ? $this->triggerOnPayment : false,
                'template_name' => $this->templateName,
                'header_notes' => $this->headerNotes,
                'footer_notes' => $this->footerNotes,
                'contact_info' => $this->contactInfo,
                'subtotal' => $this->subtotal,
                'vat' => $this->vat,
                'total' => $this->total,
                'global_discount' => $this->globalDiscount,
                'withholding_tax' => $this->withholdingTax,
                'inps_contribution' => $this->inpsContribution,
                'recurrence_mode' => $this->recurrenceMode,
                'recurrence_type' => $this->recurrenceMode === 'manual' ? $this->recurrenceType : null,
                'recurrence_interval' => $this->recurrenceMode === 'manual' ? $this->recurrenceInterval : null,
                'start_date' => $this->startDate,
                'end_date' => $this->recurrenceMode === 'manual' ? $this->endDate : null,
                'next_invoice_date' => $this->startDate,
                'max_invoices' => $this->recurrenceMode === 'manual' ? $this->maxInvoices : null,
                'is_active' => true,
                'invoices_generated' => 0,
                'last_generated_at' => null,
                'document_type' => $this->documentType,
                'ddt_number' => $this->ddt_number,
                'ddt_date' => $this->ddt_date,
                'split_payments' => $this->splitPayments,
                'due_option' => $this->dueOption,
                'due_date' => $this->dueDate,
                'payments' => json_encode($this->payments),
            ];

            $recurringInvoice = RecurringInvoice::create($data);

            // Create items
            foreach ($this->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'] * (1 + $item['vat_rate'] / 100);
                $recurringInvoice->items()->create([
                    'name' => $item['name'] ?? '',
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'],
                    'total' => $itemTotal,
                ]);
            }

            session()->flash('success', 'Fattura ricorrente creata con successo!');
            return redirect()->route('fatture-ricorrenti.lista');

        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }

    public function getPreviewHtmlProperty(): string
    {
        // Create a mock invoice for preview
        $previewNumber = $this->invoicePrefix ? $this->invoicePrefix . '-000' : '000';
        
        $mockInvoice = new \App\Models\Invoice([
            'company_id' => Auth::user()?->current_company_id ?? session('current_company_id'),
            'client_id' => $this->selectedClientId,
            'numbering_id' => $this->selectedNumberingId,
            'invoice_number' => $previewNumber,
            'issue_date' => $this->startDate,
            'fiscal_year' => \Carbon\Carbon::parse($this->startDate)->format('Y'),
            'withholding_tax' => $this->withholdingTax,
            'inps_contribution' => $this->inpsContribution,
            'payment_method_id' => $this->selectedPaymentMethodId,
            'subtotal' => $this->subtotal,
            'vat' => $this->vat,
            'total' => $this->total,
            'global_discount' => $this->globalDiscount,
            'header_notes' => $this->headerNotes,
            'document_type' => $this->documentType,
            'footer_notes' => $this->footerNotes,
            'contact_info' => $this->contactInfo,
        ]);

        // Load numbering if selected
        if ($this->selectedNumberingId) {
            $numbering = \App\Models\InvoiceNumbering::find($this->selectedNumberingId);
            if ($numbering) {
                $mockInvoice->setRelation('numbering', $numbering);
            }
        }

        // Show preview even without client selected (like regular invoice form)
        if ($this->selectedNumberingId && !empty($this->items)) {
            try {
                $renderer = new \App\Services\InvoiceRenderer($mockInvoice, $this->items, [], false, null);
                return $renderer->renderHtml();
            } catch (\Exception $e) {
                return '<div class="p-4 text-center text-gray-500">
                    <p class="mb-2">Anteprima non disponibile</p>
                    <p class="text-sm">Errore: ' . $e->getMessage() . '</p>
                </div>';
            }
        }

        return '<div class="p-4 text-center text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mb-2">Anteprima Fattura Ricorrente</p>
            <p class="text-sm">Aggiungi articoli per vedere l\'anteprima</p>
        </div>';
    }

    public function render()
    {
        return view('livewire.recurring-invoice-form');
    }
}