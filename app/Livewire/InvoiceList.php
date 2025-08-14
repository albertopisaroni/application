<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;
use App\Jobs\SendInvoiceEmailJob;
use App\Models\PaymentMethod;
use App\Jobs\SendInvoiceToSdiJob;
use App\Models\InvoiceNumbering;

class InvoiceList extends Component
{
    use WithPagination;

    public array $years = [];
    public $yearFilter = null;
    public $perPage = 10;
    public $search = '';
    public $paymentStatusFilter = null;
    public $paymentMethods = [];
    public $numberingFilter = null;

    protected $queryString = [
        'yearFilter' => ['except' => null],
        'search' => ['except' => ''],
        'paymentStatusFilter' => ['except' => null],
        'numberingFilter' => ['except' => null],    
    ];

    public function updatingYearFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->yearFilter = null;
        $this->search = '';
        $this->paymentStatusFilter = null;
        $this->numberingFilter = null;
        $this->resetPage();
    }

    public function sendInvoiceEmail($invoiceId)
    {
        $this->dispatch('confirm-send-email', invoiceId: $invoiceId);
    }

    public function confirmSendEmail($invoiceId)
    {
        try {
            SendInvoiceEmailJob::dispatch($invoiceId);
            $this->dispatch('show-success', message: 'Email in coda per l\'invio');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nell\'invio dell\'email: ' . $e->getMessage());
        }

        \Log::info('Invio email fattura', [
            'invoice_id' => $invoiceId,
            'user_id' => auth()->id(),
            'company_id' => session('current_company_id'),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    public function receivePayment($invoiceId, $paymentData = null)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            
            if ($paymentData) {
                // Create the payment record
                $paymentMethodId = is_numeric($paymentData['method']) ? (int)$paymentData['method'] : null;
                $paymentMethodName = $paymentData['method'];
                
                // If it's a numeric ID, get the payment method name
                if ($paymentMethodId) {
                    $paymentMethod = PaymentMethod::find($paymentMethodId);
                    if ($paymentMethod) {
                        $paymentMethodName = $paymentMethod->name;
                    }
                }
                
                $invoice->payments()->create([
                    'amount' => $paymentData['amount'],
                    'payment_date' => $paymentData['paymentDate'],
                    'payment_method_id' => $paymentMethodId,
                    'method' => $paymentMethodName,
                    'note' => $paymentData['note'] ?? null,
                ]);
                
                // If it's a partial payment and there's a due date, update payment schedule
                if ($paymentData['isPartial'] && $paymentData['dueDate']) {
                    $remainingAmount = $invoice->total - $invoice->payments->sum('amount');
                    
                    // Create or update payment schedule for remaining amount
                    $invoice->paymentSchedules()->create([
                        'due_date' => $paymentData['dueDate'],
                        'amount' => $remainingAmount,
                        'type' => 'amount',
                    ]);
                }
                
                $this->dispatch('show-success', message: 'Pagamento registrato con successo');
            } else {
                // Fallback: open payment modal for manual handling
                $this->dispatch('open-payment-modal', invoiceId: $invoiceId);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nella registrazione del pagamento: ' . $e->getMessage());
        }
    }

    public function resendToSdi($invoiceId)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            
            // Incrementa il tentativo di invio
            $invoice->increment('sdi_attempt');
            
            // Resetta lo stato per permettere il reinvio
            $invoice->update([
                'sdi_status' => 'pending',
                'sdi_error' => null,
                'sdi_error_description' => null,
            ]);
            
            // Rilancia il job di invio a SDI
            SendInvoiceToSdiJob::dispatch($invoiceId);
            
            $this->dispatch('show-success', message: 'Fattura in coda per il reinvio a SDI');
            
            \Log::info('Reinvio fattura a SDI', [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'sdi_attempt' => $invoice->sdi_attempt,
                'user_id' => auth()->id(),
                'company_id' => session('current_company_id'),
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Errore nel reinvio a SDI: ' . $e->getMessage());
        }
    }

    public function mount()
    {
        if ($this->yearFilter === '') {
            $this->yearFilter = null;
        }
    
        $this->years = Invoice::selectRaw('YEAR(fiscal_year) as year')
            ->whereIn('document_type', ['TD01', 'TD02', 'TD03', 'TD05', 'TD024', 'TD026'])
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
    }

    public function render()
    {
        
        $currentCompanyId = session('current_company_id');

        $query = Invoice::with(['client', 'payments'])
            ->where('company_id', $currentCompanyId)
            ->whereIn('document_type', ['TD01', 'TD02', 'TD03', 'TD05', 'TD024', 'TD026']) // Fatture ordinarie
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc');

        $allInvoices = $query->get();

        if ($this->yearFilter) {
            $query->where('fiscal_year', $this->yearFilter);
        }

        if ($this->numberingFilter) {
            $query->where('numbering_id', $this->numberingFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('client', fn($sub) =>
                    $sub->where('name', 'like', '%' . $this->search . '%')
                )->orWhere('invoice_number', 'like', '%' . $this->search . '%');
            });
        }      

        if ($this->paymentStatusFilter === 'unpaid') {
            $query->where(function ($query) {
                $query->whereHas('payments', function ($q) {
                    $q->selectRaw('SUM(amount)')->havingRaw('SUM(amount) < invoices.total');
                })->orWhereDoesntHave('payments');
            })->where('total', '>', 0);
        } elseif ($this->paymentStatusFilter === 'paid') {
            $query->where(function ($query) {
                $query->whereHas('payments', function ($q) {
                    $q->selectRaw('SUM(amount)')->havingRaw('SUM(amount) >= invoices.total');
                })->orWhere('total', '<=', 0);
            });
        }

        $invoices = $query->paginate($this->perPage);

        $unpaidInvoices = $allInvoices->filter(fn($invoice) =>
            $invoice->payments->sum('amount') < $invoice->total
        );

        $unpaidCount = $unpaidInvoices->count();
        $paidCount = $allInvoices->count() - $unpaidCount;
        $unpaidTotal = $unpaidInvoices->sum(fn($invoice) =>
            $invoice->total - $invoice->payments->sum('amount')
        );

        $numberings = InvoiceNumbering::where('company_id', $currentCompanyId)->get();

        // Load payment methods for the current company
        $this->paymentMethods = PaymentMethod::where('company_id', $currentCompanyId)->get();

        return view('livewire.invoice-list', [
            'invoices' => $invoices,
            'unpaidCount' => $unpaidCount,
            'paidCount' => $paidCount,
            'unpaidTotal' => $unpaidTotal,  
            'paymentMethods' => $this->paymentMethods,
            'numberings' => $numberings,
        ]);
    }
}