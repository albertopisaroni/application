<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Invoice;
use App\Jobs\SendInvoiceEmailJob;

class InvoiceList extends Component
{
    use WithPagination;

    public array $years = [];
    public $yearFilter = null;
    public $perPage = 10;
    public $search = '';
    public $paymentStatusFilter = null;

    protected $queryString = [
        'yearFilter' => ['except' => null],
        'search' => ['except' => ''],
        'paymentStatusFilter' => ['except' => null],

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

        return view('livewire.invoice-list', [
            'invoices' => $invoices,
            'unpaidCount' => $unpaidCount,
            'paidCount' => $paidCount,
            'unpaidTotal' => $unpaidTotal,
        ]);
    }
}