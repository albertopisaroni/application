<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Subscription;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumbering;
use App\Models\PaymentMethod;
use App\Console\Commands\ProcessRecurringInvoices;
use Carbon\Carbon;

class SubscriptionList extends Component
{
    use WithPagination;

    public array  $numberings         = [];
    public $numberingFilter           = null;  
    public $perPage                   = 10;
    public $search                    = '';
    public $paymentStatusFilter       = null;
    public array  $stripeAccounts     = [];
    public $stripeAccountFilter       = null;


    // metriche del mese
    public int   $renewalsCount   = 0;
    public float $renewalsTotal   = 0.0;

    // nuove metriche
    public int   $activeCount     = 0;
    public float $mrr             = 0.0;

    public function updatingNumberingFilter()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingStripeAccountFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->numberingFilter     = null;
        $this->search              = '';
        $this->paymentStatusFilter = null;
        $this->stripeAccountFilter = null;
        $this->resetPage();
    }

    public function mount()
    {
        $companyId = session('current_company_id');

        $this->numberings = \App\Models\InvoiceNumbering::whereHas('stripeAccount', function($q) use($companyId) {
            $q->where('company_id', $companyId);
        })->get()->toArray();

        $this->stripeAccounts = \App\Models\StripeAccount::where('company_id', $companyId)->get()->toArray();
    }

    public function render()
    {
        $companyId = session('current_company_id');

        $baseQuery = Subscription::whereHas('client', fn($q) =>
            $q->where('company_id', $companyId)
        );

        // 1) Rinnovi di questo mese (di tutti gli anni)
        $currentMonth = Carbon::now()->month;

        // 1) Prepara la query base con JOIN su clients
        $subQuery = Subscription::join('clients', 'subscriptions.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId);

        // 2) Solo rinnovi che finiscono in questo mese (di tutti gli anni, attive o past_due)
        $endRenewals = (clone $subQuery)
            ->whereMonth('subscriptions.current_period_end', $currentMonth)
            ->whereIn('subscriptions.status', ['active', 'past_due']);

        // 4) Conta e somma solo i rinnovi
        $this->renewalsCount = $endRenewals->count();
        $this->renewalsTotal = $endRenewals->sum('subscriptions.total_with_vat') / 100; // Converti da centesimi a euro

        // 2) activeCount + MRR in un’unica query
        $now = Carbon::now();

        $metriche = Subscription::whereHas('client', fn($q) =>
                $q->where('company_id', $companyId)
            )
            ->where('status','active')
            ->where('current_period_end','>=',$now)
            ->selectRaw('
                COUNT(*)           as activeCount,
                COALESCE(SUM(total_with_vat),0)  as totalFinal
            ')
            ->first();

        $this->activeCount = (int) $metriche->activeCount;
        $this->mrr         = ($metriche->totalFinal / 100) / 12; // Converti da centesimi a euro, poi dividi per 12 mesi

        // 3) Lista paginata (applica anche ordinamenti e filtri se li hai)
        
        $query = $baseQuery
            //  --- filtro numerazione solo se > 0
            ->when($this->numberingFilter > 0, fn($q) =>
                $q->whereHas('client.company.stripeAccounts', fn($qc) =>
                    $qc->where('invoice_numbering_id', $this->numberingFilter)
                )
            )
            //  --- filtro ricerca nome o email
            ->when($this->search, function($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn($q) =>
                    $q->whereHas('client', fn($qc) =>
                        $qc->where('name','like',$term)
                    )->orWhereHas('client.primaryContact', fn($qc) =>
                        $qc->where('email','like',$term)
                    )
                );
            })
            //  --- filtro stato
            ->when($this->paymentStatusFilter, function($q) {
                if ($this->paymentStatusFilter === 'unpaid') {
                    // "Non pagato" = unpaid OR past_due
                    $q->whereIn('status', ['unpaid','past_due']);
                } else {
                    $q->where('status', $this->paymentStatusFilter);
                }
            })
            //  --- filtro account Stripe
            ->when($this->stripeAccountFilter > 0, fn($q) =>
                $q->where('stripe_account_id', $this->stripeAccountFilter)
            )
            ->orderBy('start_date','desc')
            ->orderBy('id','desc');

        $subscriptions = $query
            ->with(['price.product','client.primaryContact'])
            ->paginate($this->perPage);

        return view('livewire.subscription-list', [
            'subscriptions'   => $subscriptions,
            'renewalsCount'   => $this->renewalsCount,
            'renewalsTotal'   => $this->renewalsTotal,
            'activeCount'     => $this->activeCount,
            'mrr'             => $this->mrr,
            'numberings'      => $this->numberings,
            'stripeAccounts'  => $this->stripeAccounts,
        ]);
    }

    /**
     * Create a single invoice from a Stripe subscription
     */
    public function createInvoiceFromSubscription($subscriptionId)
    {
        try {
            $subscription = Subscription::with(['client', 'price.product'])->findOrFail($subscriptionId);
            
            // Get default numbering for the company
            $numbering = InvoiceNumbering::where('company_id', session('current_company_id'))
                ->first();
            
            if (!$numbering) {
                session()->flash('error', 'Nessuna numerazione disponibile per questa company');
                return;
            }

            // Get default payment method
            $paymentMethod = PaymentMethod::where('company_id', session('current_company_id'))
                ->first();

            // Create invoice
            $invoiceNumber = $numbering->nextNumber(now()->year);
            $invoice = Invoice::create([
                'company_id' => session('current_company_id'),
                'client_id' => $subscription->client_id,
                'numbering_id' => $numbering->id,
                'payment_method_id' => $paymentMethod?->id ?: 1,
                'invoice_number' => $invoiceNumber,
                'issue_date' => now()->toDateString(),
                'fiscal_year' => now()->year,
                'document_type' => 'TD01',
                'subtotal' => $subscription->subtotal_amount / 100, // Convert from cents
                'vat' => $subscription->vat_amount / 100,
                'total' => $subscription->total_with_vat / 100,
                'global_discount' => $subscription->discount_amount / 100,
                'withholding_tax' => false,
                'inps_contribution' => false,
                'header_notes' => "Fattura generata da abbonamento Stripe: {$subscription->stripe_subscription_id}",
                'footer_notes' => null,
                'contact_info' => null,
                'save_notes_for_future' => false,
                'sdi_sent_at' => null,
                'sdi_received_at' => null,
                'sdi_attempt' => 1,
            ]);

            // Create invoice item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'name' => $subscription->price->product->name ?? 'Abbonamento',
                'description' => "Abbonamento {$subscription->price->product->name}" . 
                    ($subscription->current_period_start && $subscription->current_period_end 
                        ? " - Periodo: " . $subscription->current_period_start->format('d/m/Y') . ' - ' . $subscription->current_period_end->format('d/m/Y')
                        : ""),
                'quantity' => $subscription->quantity ?? 1,
                'unit_of_measure' => '',
                'unit_price' => $subscription->unit_amount / 100,
                'vat_rate' => $subscription->vat_rate ?? 22,
            ]);

            // The numbering counter has already been incremented by nextNumber()

            // Add payment schedule
            $invoice->paymentSchedules()->create([
                'due_date' => now()->addDays(30)->toDateString(),
                'amount' => $invoice->total,
                'type' => 'amount',
                'percent' => null,
            ]);

            session()->flash('success', "Fattura #{$invoice->invoice_number} creata con successo dall'abbonamento! Puoi ora modificarla o inviarla dalla lista fatture.");
            
            // Redirect to invoice list
            return redirect()->route('fatture.lista');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore nella creazione della fattura: ' . $e->getMessage());
        }
    }

}